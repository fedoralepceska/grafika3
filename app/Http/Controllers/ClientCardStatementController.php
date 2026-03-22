<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientCardStatement;
use App\Models\Faktura;
use App\Models\IncomingFaktura;
use App\Models\Item;
use App\Models\TradeInvoice;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;


class ClientCardStatementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = ClientCardStatement::with('client');

            if ($request->filled('searchQuery')) {
                $searchQuery = trim((string) $request->input('searchQuery'));
                if ($searchQuery !== '') {
                    $query->where(function ($q) use ($searchQuery) {
                        $q->where('account', 'like', "%{$searchQuery}%")
                            ->orWhere('name', 'like', "%{$searchQuery}%")
                            ->orWhere('bank', 'like', "%{$searchQuery}%");
                    });
                }
            }

            if ($request->has('sortOrder')) {
                $sortOrder = $request->input('sortOrder');
                $query->orderBy('created_at', $sortOrder);
            }

            if ($request->filled('client_id')) {
                $query->where('client_id', (int) $request->input('client_id'));
            } elseif ($request->has('client') && $request->input('client') !== 'All') {
                $client = $request->input('client');
                $query->where('client_id', $client);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }
            if ($request->filled('fiscal_year')) {
                $query->whereYear('created_at', (int) $request->input('fiscal_year'));
            }
            if ($request->filled('month')) {
                $month = (int) $request->input('month');
                if ($month >= 1 && $month <= 12) {
                    $query->whereMonth('created_at', $month);
                }
            }
            $perPage = (int) $request->input('per_page', 20);
            $perPage = max(1, min($perPage, 200));
            $clientCards = $query->paginate($perPage)->withQueryString();
            if ($request->wantsJson()) {
                return response()->json($clientCards);
            }

            return Inertia::render('Finance/CardStatements', [
                'clientCards' => $clientCards,
                'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();

        // Find the ClientCardStatement based on a unique identifier (e.g., client_id)
        $clientCardStatement = ClientCardStatement::firstOrCreate(['client_id' => $data['client_id']], $data);

        $clientCardStatement->update($data);

        // The $clientCardStatement will now be the existing record if found, or a new one if created
        $clientCardStatement->save(); // This might be redundant as `firstOrCreate` already saves

        return response()->json([
            'message' => 'ClientCardStatement created or updated successfully',
            'data' => $clientCardStatement
        ]);
    }

    /**
     * Display the specified resource.
     */

    public function show(Request $request, $id)
    {
        $cardStatement = ClientCardStatement::findOrFail($id);

        // Fetch the client
        $client = $cardStatement->client;

        // Get the date filters from the request - if not provided, show ALL data (no date restriction)
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        // Fetch items related to the client
        $itemsQuery = Item::query()
            ->where('client_id', $cardStatement->client_id);

        if ($fromDate) {
            $itemsQuery->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $itemsQuery->whereDate('created_at', '<=', $toDate);
        }

        $items = $itemsQuery->get();

        // Fetch relevant fakturas (isInvoiced=true and client matches), honoring override exclusively
        $fakturasQuery = Faktura::query()
            ->where('isInvoiced', 1)
            ->where(function ($query) use ($cardStatement) {
                $query
                    // Include fakturas explicitly assigned to this client_id
                    ->where('client_id', $cardStatement->client_id)
                    // Or, if faktura has NO override (client_id is null), include when underlying invoices/jobs belong to this client
                    ->orWhere(function ($q) use ($cardStatement) {
                        $q->whereNull('client_id')
                          ->where(function ($sub) use ($cardStatement) {
                              $sub->whereHas('invoices', function ($subQuery) use ($cardStatement) {
                                      $subQuery->where('client_id', $cardStatement->client_id);
                                  })
                                  ->orWhereHas('jobs', function ($subQuery) use ($cardStatement) {
                                      $subQuery->where('client_id', $cardStatement->client_id);
                                  });
                          });
                    });
            });

        if ($fromDate) {
            $fakturasQuery->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $fakturasQuery->whereDate('created_at', '<=', $toDate);
        }

        $fakturas = $fakturasQuery->with(['invoices.jobs', 'jobs'])->get();

        // Fetch relevant incoming fakturas (client_id matches) - apply date filters
        $incomingFakturasQuery = IncomingFaktura::query()
            ->where('client_id', $cardStatement->client_id);

        if ($fromDate) {
            $incomingFakturasQuery->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $incomingFakturasQuery->whereDate('created_at', '<=', $toDate);
        }

        $incomingFakturas = $incomingFakturasQuery->get();
        $totalIncomingFromFaktura = $incomingFakturas->sum('amount');

        // Fetch relevant trade invoices (client_id matches and status is sent or paid)
        $tradeInvoicesQuery = TradeInvoice::query()
            ->where('client_id', $cardStatement->client_id)
            ->whereIn('status', ['sent', 'paid']);

        if ($fromDate) {
            $tradeInvoicesQuery->whereDate('invoice_date', '>=', $fromDate);
        }

        if ($toDate) {
            $tradeInvoicesQuery->whereDate('invoice_date', '<=', $toDate);
        }

        $tradeInvoices = $tradeInvoicesQuery->get();

        $formattedItems = $items->flatMap(function ($item) {
            $number = sprintf('%03d/%d', $item->id, $item->created_at->format('Y'));

            $rows = [];

            if ($item->income > 0) {
                $rows[] = [
                    'date' => $item->created_at->format('Y-m-d'),
                    'document' => 'Statement Income',
                    'number' => $number,
                    'incoming_invoice' => 0,
                    'output_invoice' => 0,
                    'statement_income' => $item->income,
                    'statement_expense' => 0,
                    'comment' => $item->comment,
                ];
            }

            if ($item->expense > 0) {
                $rows[] = [
                    'date' => $item->created_at->format('Y-m-d'),
                    'document' => 'Statement Expense',
                    'number' => $number,
                    'incoming_invoice' => 0,
                    'output_invoice' => 0,
                    'statement_income' => 0,
                    'statement_expense' => $item->expense,
                    'comment' => $item->comment,
                ];
            }

            return $rows;
        })->toArray();

        // Format incoming invoices separately
        $formattedIncomingInvoices = $incomingFakturas->map(function ($incomingFaktura) {
            return [
                'date' => $incomingFaktura->created_at->format('Y-m-d'),
                'document' => 'Incoming Invoice',
                'number' => sprintf('%03d/%d', $incomingFaktura->id, $incomingFaktura->created_at->format('Y')),
                'incoming_invoice' => $incomingFaktura->amount,
                'output_invoice' => 0,
                'statement_income' => 0,
                'statement_expense' => 0,
                'comment' => $incomingFaktura->description ?? '',
            ];
        })->toArray();

        // Format data for fakturas
        $formattedFakturas = $fakturas->map(function ($faktura) use ($cardStatement) {
            $invoiceTotal = 0;
            $documentType = 'Output Invoice';
            
            // If faktura has explicit client override
            if (!is_null($faktura->client_id)) {
                // Only count totals if the override matches this statement's client; otherwise exclude entirely
                if ((int)$faktura->client_id === (int)$cardStatement->client_id) {
                    foreach ($faktura->invoices as $invoice) {
                        $invoice->loadMissing('jobs');
                        $invoiceTotal += $invoice->jobs->sum('salePrice');
                    }
                } else {
                    $invoiceTotal = 0; // ensure excluded from other clients
                }
            } else {
                // No override: sum only the invoices belonging to this client
                $clientInvoices = $faktura->invoices->where('client_id', $cardStatement->client_id);
                if ($clientInvoices->isNotEmpty()) {
                    foreach ($clientInvoices as $invoice) {
                        $invoice->loadMissing('jobs');
                        $invoiceTotal += $invoice->jobs->sum('salePrice');
                    }
                }
            }
            
            // Handle split invoices (jobs directly assigned to faktura)
            if ($faktura->is_split_invoice) {
                $clientJobs = $faktura->jobs->where('client_id', $cardStatement->client_id);
                if ($clientJobs->isNotEmpty()) {
                    $invoiceTotal += $clientJobs->sum('salePrice');
                    $documentType = 'Output Invoice (Split)';
                }
            }
            
            // Skip if no relevant invoices or jobs for this client
            if ($invoiceTotal == 0) {
                return null;
            }

            return [
                'date' => $faktura->created_at->format('Y-m-d'),
                'document' => $documentType,
                'number' => sprintf('%03d/%d', $faktura->id, $faktura->created_at->format('Y')),
                'incoming_invoice' => 0,
                'output_invoice' => $invoiceTotal,
                'statement_income' => 0,
                'statement_expense' => 0,
                'comment' => $faktura->comment,
            ];
        })->filter()->toArray();

        // Format data for trade invoices
        $formattedTradeInvoices = $tradeInvoices->map(function ($tradeInvoice) {
            return [
                'date' => $tradeInvoice->invoice_date->format('Y-m-d'),
                'document' => 'Outcome Invoice',
                'number' => $tradeInvoice->invoice_number,
                'incoming_invoice' => 0,
                'output_invoice' => $tradeInvoice->total_amount,
                'statement_income' => 0,
                'statement_expense' => 0,
                'comment' => $tradeInvoice->notes ?? '',
            ];
        })->toArray();

        $tableData = array_merge($formattedItems, $formattedIncomingInvoices, $formattedFakturas, $formattedTradeInvoices);
        usort($tableData, fn ($a, $b) => strtotime($a['date']) <=> strtotime($b['date']));

        // Optional row-level filters (search + fiscal year + month on row date)
        if ($request->filled('searchQuery')) {
            $needle = mb_strtolower(trim((string) $request->input('searchQuery')));
            if ($needle !== '') {
                $tableData = array_values(array_filter($tableData, function ($row) use ($needle) {
                    $hay = mb_strtolower(
                        ($row['date'] ?? '').' '.
                        ($row['document'] ?? '').' '.
                        ($row['number'] ?? '').' '.
                        ($row['comment'] ?? '')
                    );

                    return str_contains($hay, $needle);
                }));
            }
        }

        if ($request->filled('fiscal_year')) {
            $fy = (int) $request->input('fiscal_year');
            $tableData = array_values(array_filter($tableData, function ($row) use ($fy) {
                $ts = strtotime($row['date'] ?? '');
                if ($ts === false) {
                    return false;
                }

                return (int) date('Y', $ts) === $fy;
            }));
        }

        if ($request->filled('month')) {
            $mo = (int) $request->input('month');
            if ($mo >= 1 && $mo <= 12) {
                $tableData = array_values(array_filter($tableData, function ($row) use ($mo) {
                    $ts = strtotime($row['date'] ?? '');
                    if ($ts === false) {
                        return false;
                    }

                    return (int) date('n', $ts) === $mo;
                }));
            }
        }

        $rows = collect($tableData);
        $totalStatementIncome = (float) $rows->sum('statement_income');
        $totalIncomingInvoice = (float) $rows->sum('incoming_invoice');
        $totalStatementExpense = (float) $rows->sum('statement_expense');
        $totalOutputInvoice = (float) $rows->sum('output_invoice');

        $owes = $totalStatementExpense + $totalOutputInvoice;
        $requests = $totalStatementIncome + $totalIncomingInvoice;

        // Opening balance: only when no date range filter (full statement view)
        $hasDateRange = $fromDate || $toDate;
        if (! $hasDateRange) {
            if ($cardStatement->initial_cash < 0) {
                $owes += $cardStatement->initial_cash;
            } else {
                $requests += $cardStatement->initial_cash;
            }
        }

        $totalBalance = $owes > $requests ? $owes - $requests : $requests - $owes;

        // Paginate filtered table data
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 200));
        $totalRows = count($tableData);
        $paginatedTableData = collect($tableData)->slice(($page - 1) * $perPage, $perPage)->values();
        $pagination = new LengthAwarePaginator($paginatedTableData, $totalRows, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        if ($request->wantsJson()) {
            $payload = $pagination->toArray();
            $payload['owes'] = $owes;
            $payload['requests'] = $requests;
            $payload['balance'] = $totalBalance;

            return response()->json($payload);
        }

        return Inertia::render('Finance/ClientCardStatement', [
            'cardStatement' => $cardStatement,
            'client' => $client,
            'tableData' => $pagination,
            'owes' => $owes,
            'requests' => $requests,
            'balance' => $totalBalance,
        ]);
    }





    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClientCardStatement $clientCardStatement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $clientCardStatement = ClientCardStatement::with('client_id')->findOrFail($id);

        $clientCardStatement->update($request->all());

        return response()->json(['message' => 'ClientCardStatement updated successfully', 'data' => $clientCardStatement]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClientCardStatement $clientCardStatement)
    {
        //
    }

    public function getCCSByClientId(int $id)
    {
        $clientCardStatement = ClientCardStatement::where('client_id', $id)->first();

        if ($clientCardStatement) {
            return response()->json($clientCardStatement);
        }
        else {
            return [];
        }
    }
}
