<?php

namespace App\Http\Controllers;

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

            if ($request->has('searchQuery')) {
                $searchQuery = $request->input('searchQuery');
                $query->where('account', 'like', "%$searchQuery%");
            }

            if ($request->has('sortOrder')) {
                $sortOrder = $request->input('sortOrder');
                $query->orderBy('created_at', $sortOrder);
            }

            if ($request->has('client')) {
                $client = $request->input('client');
                if ($client != 'All') {
                    $query->where('client_id', $client);
                }
            }
            $clientCards = $query->latest()->paginate(10);
            if ($request->wantsJson()) {
                return response()->json($clientCards);
            }

            return Inertia::render('Finance/CardStatements', [
                'clientCards' => $clientCards,
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

        // Get the date filters from the request
        $fromDate = $request->query('from_date') ?? now()->startOfYear()->format('Y-m-d');
        $toDate = $request->query('to_date') ?? now()->format('Y-m-d');

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

        // Fetch relevant fakturas (isInvoiced=true and client_id matches)
        $fakturasQuery = Faktura::query()
            ->where('isInvoiced', 1)
            ->whereHas('invoices', function ($query) use ($cardStatement) {
                $query->where('client_id', $cardStatement->client_id);
            });

        // Fetch relevant incoming fakturas (client_id matches)
        $incomingFakturasQuery = IncomingFaktura::query()
            ->where('client_id', $cardStatement->client_id)
            ->whereYear('created_at', now()->year);

        $totalIncomingFromFaktura = $incomingFakturasQuery->get()->sum('amount');

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

        if ($fromDate) {
            $fakturasQuery->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $fakturasQuery->whereDate('created_at', '<=', $toDate);
        }

        $fakturas = $fakturasQuery->get();

        $formattedItems = $items->flatMap(function ($item) use ($totalIncomingFromFaktura) {
            $number = sprintf('%03d/%d', $item->id, $item->created_at->format('Y'));

            $rows = [];

            if ($totalIncomingFromFaktura > 0) {
                $rows[] = [
                    'date' => $item->created_at->format('Y-m-d'),
                    'document' => 'Incoming Invoice',
                    'number' => $number,
                    'incoming_invoice' => $totalIncomingFromFaktura,
                    'output_invoice' => 0,
                    'statement_income' => 0,
                    'statement_expense' => 0,
                    'comment' => $item->comment,
                ];
            }

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

        // Format data for fakturas
        $formattedFakturas = $fakturas->map(function ($faktura) use ($cardStatement) {
            $invoice = $faktura->invoices->first();
            if (!$invoice || $invoice->client_id !== $cardStatement->client_id) {
                return null;
            }

            $invoice->load('jobs');
            $invoiceTotal = $invoice->jobs->sum('salePrice');

            return [
                'date' => $faktura->created_at->format('Y-m-d'),
                'document' => 'Output Invoice',
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

        $tableData = array_merge($formattedItems, $formattedFakturas, $formattedTradeInvoices);
        usort($tableData, fn ($a, $b) => strtotime($a['date']) <=> strtotime($b['date']));

        // Paginate table data
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 20);
        $paginatedTableData = collect($tableData)->slice(($page - 1) * $perPage, $perPage)->values();
        $pagination = new \Illuminate\Pagination\LengthAwarePaginator($paginatedTableData, count($tableData), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        // Calculate total statement expense
        $totalStatementExpense = collect($formattedItems)->sum('statement_expense');

        // Calculate total output invoice amount (including trade invoices)
        $totalOutputInvoice = collect($formattedFakturas)->sum('output_invoice') + collect($formattedTradeInvoices)->sum('output_invoice');

        // Calculate total statement income
        $totalStatementIncome = collect($formattedItems)->sum('statement_income');

        // Calculate total incoming invoice
        $totalIncomingInvoice = collect($formattedItems)->sum('incoming_invoice');

        // Calculate total amount owed
        $owes = $totalStatementExpense + $totalOutputInvoice;

        // Calculate total amount requested (income)
        $requests = $totalStatementIncome + $totalIncomingInvoice;

        if ($cardStatement->initial_cash < 0) {
            $owes += $cardStatement->initial_cash;
        } else {
            $requests += $cardStatement->initial_cash;
        }

        $totalBalance = $owes > $requests ? $owes - $requests : $requests - $owes;



        if ($request->wantsJson()) {
            return response()->json($pagination);
        }

        return Inertia::render('Finance/ClientCardStatement', [
            'cardStatement' => $cardStatement,
            'client' => $client, // Include client information
            'tableData' => $pagination,
            'owes' => $owes,
            'requests' => $requests,
            'balance' => $totalBalance
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
