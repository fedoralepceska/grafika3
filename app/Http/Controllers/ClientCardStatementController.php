<?php

namespace App\Http\Controllers;

use App\Models\ClientCardStatement;
use App\Models\Faktura;
use App\Models\Item;
use Illuminate\Http\Request;
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
    public function show($id)
    {
        $cardStatement = ClientCardStatement::query()->findOrFail($id);

        // Fetch items related to the client
        $items = Item::query()
            ->where('client_id', $cardStatement->client_id)
            ->get();

        // Fetch relevant fakturas (isInvoices=true and client_id matches)
        $fakturas = Faktura::query()
            ->where('isInvoiced', true)
            ->whereHas('invoices', function($query) use ($cardStatement) {
                $query->where('client_id', $cardStatement->client_id);
            })
            ->get();

        // Format data for items
        $formattedItems = $items->map(function ($item) {
            $document = $item->income ? 'Statement Income' : 'Statement Expense';
            $number = sprintf('%03d/%d', $item->id, $item->created_at->format('Y'));
            $statementValue = $item->income ?: $item->expense;

            return [
                'date' => $item->created_at->format('Y-m-d'),
                'document' => $document,
                'number' => $number,
                'incoming_invoice' => 0,
                'output_invoice' => 0,
                'statement_income' => $item->income,
                'statement_expense' => $item->expense, // Expense only if not income
                'comment' => $item->comment,
            ];
        })->toArray();

        // Format data for faktūras
        $formattedFakturas = $fakturas->map(function ($faktura) use ($cardStatement) {
            $invoice = $faktura->invoices->first();
            if (!$invoice || $invoice->client_id !== $cardStatement->client_id) {
                return null; // Skip fakturas without a matching client in the first invoice
            }

            // Eager load jobs for efficient access
            $invoice->load('jobs'); // Load jobs relationship for the current invoice

            $invoiceTotal = $invoice->jobs->sum('salePrice'); // Sum salePrice from loaded jobs

            return [
                'date' => $faktura->created_at->format('Y-m-d'),
                'document' => 'Output invoice',
                'number' => sprintf('%03d/%d', $faktura->id, $faktura->created_at->format('Y')),
                'incoming_invoice' => 0,
                'output_invoice' => $invoiceTotal,
                'statement_income' => 0,
                'statement_expense' => 0,
                'comment' => $faktura->comment,
            ];
        })->toArray();

        // Merge formatted data for both sources
        $tableData = array_merge($formattedItems, $formattedFakturas);

        // Sort data by date (ascending)
        usort($tableData, function ($a, $b) {
            return strtotime($a['date']) <=> strtotime($b['date']);
        });

        $formattedItems = collect($formattedItems);
        $formattedFakturas = collect($formattedFakturas);

        // Calculate total statement expense
        $totalStatementExpense = $formattedItems->sum('statement_expense');

        // Calculate total output invoice amount
        $totalOutputInvoice = $formattedFakturas->sum('output_invoice');

        // Calculate total statement income
        $totalStatementIncome = $formattedItems->sum('statement_income');

        // Calculate total incoming invoice
        $totalIncomingInvoice = $formattedItems->sum('incoming_invoice');

        // Calculate total amount owed
        $owes = $totalStatementExpense + $totalOutputInvoice;

        // Calculate total amount requested (income)
        $requests = $totalStatementIncome + $totalIncomingInvoice;

        $totalBalance = $owes > $requests ? $owes - $requests : $requests - $owes;

        return Inertia::render('Finance/ClientCardStatement', [
            'cardStatement' => $cardStatement,
            'tableData' => $tableData,
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
