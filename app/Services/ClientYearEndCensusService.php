<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientCardStatement;
use App\Models\ClientYearEndEntry;
use App\Models\Faktura;
use App\Models\FiscalYearClosure;
use App\Models\IncomingFaktura;
use App\Models\Item;
use App\Models\TradeInvoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClientYearEndCensusService
{
    /**
     * Calculate balance for a single client for a given year
     */
    public function calculateClientBalance(int $clientId, int $year): array
    {
        $cardStatement = ClientCardStatement::where('client_id', $clientId)->first();
        
        if (!$cardStatement) {
            return [
                'initial_balance' => 0,
                'total_output_invoices' => 0,
                'total_trade_invoices' => 0,
                'total_statement_expenses' => 0,
                'total_incoming_invoices' => 0,
                'total_statement_income' => 0,
                'calculated_balance' => 0,
            ];
        }

        $fromDate = "{$year}-01-01";
        $toDate = "{$year}-12-31";

        // Get output invoices from fakturas
        $totalOutputInvoices = $this->calculateOutputInvoices($cardStatement, $fromDate, $toDate);

        // Get trade invoices
        $totalTradeInvoices = TradeInvoice::where('client_id', $clientId)
            ->whereIn('status', ['sent', 'paid'])
            ->whereDate('invoice_date', '>=', $fromDate)
            ->whereDate('invoice_date', '<=', $toDate)
            ->sum('total_amount');

        // Get statement expenses and income from items
        $items = Item::where('client_id', $clientId)
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->get();

        $totalStatementExpenses = $items->sum('expense');
        $totalStatementIncome = $items->sum('income');

        // Get incoming invoices
        $totalIncomingInvoices = IncomingFaktura::where('client_id', $clientId)
            ->whereYear('created_at', $year)
            ->sum('amount');

        // Calculate balance following existing logic
        // Owes (they owe us) = expenses + output invoices + trade invoices
        $owes = $totalStatementExpenses + $totalOutputInvoices + $totalTradeInvoices;

        // Requests (we owe them) = income + incoming invoices
        $requests = $totalStatementIncome + $totalIncomingInvoices;

        // Apply initial balance
        $initialCash = (float) $cardStatement->initial_cash;
        if ($initialCash < 0) {
            $owes += abs($initialCash);
        } else {
            $requests += $initialCash;
        }

        // Final balance: positive = we owe them, negative = they owe us
        $calculatedBalance = $requests - $owes;

        return [
            'initial_balance' => $initialCash,
            'total_output_invoices' => $totalOutputInvoices,
            'total_trade_invoices' => $totalTradeInvoices,
            'total_statement_expenses' => $totalStatementExpenses,
            'total_incoming_invoices' => $totalIncomingInvoices,
            'total_statement_income' => $totalStatementIncome,
            'calculated_balance' => $calculatedBalance,
        ];
    }


    /**
     * Calculate output invoices from fakturas (following ClientCardStatementController logic)
     */
    private function calculateOutputInvoices(ClientCardStatement $cardStatement, string $fromDate, string $toDate): float
    {
        $fakturas = Faktura::query()
            ->where('isInvoiced', 1)
            ->where(function ($query) use ($cardStatement) {
                $query
                    ->where('client_id', $cardStatement->client_id)
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
            })
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->with(['invoices.jobs', 'jobs'])
            ->get();

        $total = 0;

        foreach ($fakturas as $faktura) {
            $invoiceTotal = 0;

            if (!is_null($faktura->client_id)) {
                if ((int)$faktura->client_id === (int)$cardStatement->client_id) {
                    foreach ($faktura->invoices as $invoice) {
                        $invoice->loadMissing('jobs');
                        $invoiceTotal += $invoice->jobs->sum('salePrice');
                    }
                }
            } else {
                $clientInvoices = $faktura->invoices->where('client_id', $cardStatement->client_id);
                foreach ($clientInvoices as $invoice) {
                    $invoice->loadMissing('jobs');
                    $invoiceTotal += $invoice->jobs->sum('salePrice');
                }
            }

            // Handle split invoices
            if ($faktura->is_split_invoice) {
                $clientJobs = $faktura->jobs->where('client_id', $cardStatement->client_id);
                $invoiceTotal += $clientJobs->sum('salePrice');
            }

            $total += $invoiceTotal;
        }

        return $total;
    }

    /**
     * Get or create census entries for all clients with card statements for a year
     */
    public function getCensusEntries(int $year): Collection
    {
        $clientsWithStatements = ClientCardStatement::with('client')->get();

        foreach ($clientsWithStatements as $cardStatement) {
            $entry = ClientYearEndEntry::firstOrNew([
                'fiscal_year' => $year,
                'client_id' => $cardStatement->client_id,
            ]);

            // Only calculate if entry is new or pending (not closed)
            if (!$entry->exists || $entry->status === 'pending') {
                $balanceData = $this->calculateClientBalance($cardStatement->client_id, $year);
                
                $entry->client_card_statement_id = $cardStatement->id;
                $entry->initial_balance = $balanceData['initial_balance'];
                $entry->total_output_invoices = $balanceData['total_output_invoices'];
                $entry->total_trade_invoices = $balanceData['total_trade_invoices'];
                $entry->total_statement_expenses = $balanceData['total_statement_expenses'];
                $entry->total_incoming_invoices = $balanceData['total_incoming_invoices'];
                $entry->total_statement_income = $balanceData['total_statement_income'];
                $entry->calculated_balance = $balanceData['calculated_balance'];
                $entry->save();
            }
        }

        return ClientYearEndEntry::forFiscalYear($year)
            ->with(['client', 'clientCardStatement'])
            ->get();
    }

    /**
     * Recalculate a single client's balance (if not closed)
     */
    public function recalculateEntry(ClientYearEndEntry $entry): ClientYearEndEntry
    {
        if ($entry->status === 'closed') {
            return $entry;
        }

        $balanceData = $this->calculateClientBalance($entry->client_id, $entry->fiscal_year);
        
        $entry->update([
            'initial_balance' => $balanceData['initial_balance'],
            'total_output_invoices' => $balanceData['total_output_invoices'],
            'total_trade_invoices' => $balanceData['total_trade_invoices'],
            'total_statement_expenses' => $balanceData['total_statement_expenses'],
            'total_incoming_invoices' => $balanceData['total_incoming_invoices'],
            'total_statement_income' => $balanceData['total_statement_income'],
            'calculated_balance' => $balanceData['calculated_balance'],
        ]);

        return $entry->fresh();
    }


    /**
     * Update adjusted balance for an entry
     */
    public function adjustBalance(ClientYearEndEntry $entry, ?float $adjustedBalance): ClientYearEndEntry
    {
        if ($entry->status === 'closed') {
            throw new \Exception('Cannot adjust balance for a closed entry');
        }

        $entry->update(['adjusted_balance' => $adjustedBalance]);
        return $entry->fresh();
    }

    /**
     * Mark client as ready to close
     */
    public function markReadyToClose(ClientYearEndEntry $entry): ClientYearEndEntry
    {
        if ($entry->status === 'closed') {
            throw new \Exception('Entry is already closed');
        }

        $entry->update(['status' => 'ready_to_close']);
        return $entry->fresh();
    }

    /**
     * Revert client back to pending status
     */
    public function revertToPending(ClientYearEndEntry $entry): ClientYearEndEntry
    {
        if ($entry->status === 'closed') {
            throw new \Exception('Cannot revert a closed entry');
        }

        $entry->update(['status' => 'pending']);
        return $entry->fresh();
    }


    /**
     * Close year for selected clients (partial or all ready clients)
     */
    public function closeYear(int $year, ?array $clientIds = null): FiscalYearClosure
    {
        // Check if previous years are closed
        $this->validateSequentialYearClosure($year);

        $query = ClientYearEndEntry::forFiscalYear($year)
            ->where('status', 'ready_to_close');

        if ($clientIds !== null) {
            $query->whereIn('client_id', $clientIds);
        }

        $entriesToClose = $query->get();

        if ($entriesToClose->isEmpty()) {
            throw new \Exception('No clients are ready to close');
        }

        DB::beginTransaction();

        try {
            $closedCount = 0;
            $updatedBalances = [];

            foreach ($entriesToClose as $entry) {
                $finalBalance = $entry->final_balance;

                // Update the client's initial_cash for next year
                ClientCardStatement::where('id', $entry->client_card_statement_id)
                    ->update(['initial_cash' => $finalBalance]);

                // Mark entry as closed
                $entry->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);

                $closedCount++;
                $updatedBalances[] = [
                    'client_id' => $entry->client_id,
                    'client_name' => $entry->client->name ?? 'Unknown',
                    'final_balance' => $finalBalance,
                ];
            }

            // Check if all entries for this year are now closed
            $remainingOpen = ClientYearEndEntry::forFiscalYear($year)
                ->whereIn('status', ['pending', 'ready_to_close'])
                ->count();

            // Create or update fiscal year closure record
            $closure = FiscalYearClosure::updateOrCreate(
                [
                    'fiscal_year' => $year,
                    'module' => 'clients',
                ],
                [
                    'closed_at' => now(),
                    'closed_by' => auth()->id(),
                    'summary' => [
                        'closed_count' => $closedCount,
                        'remaining_open' => $remainingOpen,
                        'is_fully_closed' => $remainingOpen === 0,
                        'updated_balances' => $updatedBalances,
                    ],
                ]
            );

            DB::commit();

            return $closure->load('closedByUser');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * Get detailed breakdown of transactions for a client
     */
    public function getClientBreakdown(int $clientId, int $year): array
    {
        $cardStatement = ClientCardStatement::where('client_id', $clientId)->first();
        
        if (!$cardStatement) {
            return ['error' => 'Client card statement not found'];
        }

        $fromDate = "{$year}-01-01";
        $toDate = "{$year}-12-31";

        // Get output invoices details
        $outputInvoices = $this->getOutputInvoicesBreakdown($cardStatement, $fromDate, $toDate);

        // Get trade invoices details
        $tradeInvoices = TradeInvoice::where('client_id', $clientId)
            ->whereIn('status', ['sent', 'paid'])
            ->whereDate('invoice_date', '>=', $fromDate)
            ->whereDate('invoice_date', '<=', $toDate)
            ->select('id', 'invoice_number', 'invoice_date', 'total_amount', 'status')
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->id,
                'number' => $inv->invoice_number,
                'date' => $inv->invoice_date->format('Y-m-d'),
                'amount' => (float) $inv->total_amount,
            ]);

        // Get items (expenses and income)
        $items = Item::where('client_id', $clientId)
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->select('id', 'created_at', 'income', 'expense', 'comment')
            ->get();

        $statementExpenses = $items->where('expense', '>', 0)->map(fn($item) => [
            'id' => $item->id,
            'date' => $item->created_at->format('Y-m-d'),
            'amount' => (float) $item->expense,
            'comment' => $item->comment,
        ])->values();

        $statementIncome = $items->where('income', '>', 0)->map(fn($item) => [
            'id' => $item->id,
            'date' => $item->created_at->format('Y-m-d'),
            'amount' => (float) $item->income,
            'comment' => $item->comment,
        ])->values();

        // Get incoming invoices
        $incomingInvoices = IncomingFaktura::where('client_id', $clientId)
            ->whereYear('created_at', $year)
            ->select('id', 'created_at', 'amount')
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->id,
                'date' => $inv->created_at->format('Y-m-d'),
                'amount' => (float) $inv->amount,
            ]);

        // Calculate totals
        $totalOutputInvoices = $outputInvoices->sum('amount');
        $totalTradeInvoices = $tradeInvoices->sum('amount');
        $totalStatementExpenses = $statementExpenses->sum('amount');
        $totalStatementIncome = $statementIncome->sum('amount');
        $totalIncomingInvoices = $incomingInvoices->sum('amount');

        $initialCash = (float) $cardStatement->initial_cash;

        // Calculate balance
        $owes = $totalStatementExpenses + $totalOutputInvoices + $totalTradeInvoices;
        $requests = $totalStatementIncome + $totalIncomingInvoices;

        if ($initialCash < 0) {
            $owes += abs($initialCash);
        } else {
            $requests += $initialCash;
        }

        $calculatedBalance = $requests - $owes;

        return [
            'client_id' => $clientId,
            'fiscal_year' => $year,
            'initial_balance' => $initialCash,
            'output_invoices' => [
                'items' => $outputInvoices,
                'total' => $totalOutputInvoices,
            ],
            'trade_invoices' => [
                'items' => $tradeInvoices,
                'total' => $totalTradeInvoices,
            ],
            'statement_expenses' => [
                'items' => $statementExpenses,
                'total' => $totalStatementExpenses,
            ],
            'statement_income' => [
                'items' => $statementIncome,
                'total' => $totalStatementIncome,
            ],
            'incoming_invoices' => [
                'items' => $incomingInvoices,
                'total' => $totalIncomingInvoices,
            ],
            'calculated_balance' => $calculatedBalance,
            'balance_direction' => $calculatedBalance >= 0 ? 'we_owe' : 'they_owe',
        ];
    }

    /**
     * Get detailed output invoices breakdown
     */
    private function getOutputInvoicesBreakdown(ClientCardStatement $cardStatement, string $fromDate, string $toDate): Collection
    {
        $fakturas = Faktura::query()
            ->where('isInvoiced', 1)
            ->where(function ($query) use ($cardStatement) {
                $query
                    ->where('client_id', $cardStatement->client_id)
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
            })
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->with(['invoices.jobs', 'jobs'])
            ->get();

        $result = collect();

        foreach ($fakturas as $faktura) {
            $invoiceTotal = 0;

            if (!is_null($faktura->client_id)) {
                if ((int)$faktura->client_id === (int)$cardStatement->client_id) {
                    foreach ($faktura->invoices as $invoice) {
                        $invoice->loadMissing('jobs');
                        $invoiceTotal += $invoice->jobs->sum('salePrice');
                    }
                }
            } else {
                $clientInvoices = $faktura->invoices->where('client_id', $cardStatement->client_id);
                foreach ($clientInvoices as $invoice) {
                    $invoice->loadMissing('jobs');
                    $invoiceTotal += $invoice->jobs->sum('salePrice');
                }
            }

            if ($faktura->is_split_invoice) {
                $clientJobs = $faktura->jobs->where('client_id', $cardStatement->client_id);
                $invoiceTotal += $clientJobs->sum('salePrice');
            }

            if ($invoiceTotal > 0) {
                $result->push([
                    'id' => $faktura->id,
                    'number' => sprintf('%03d/%d', $faktura->id, $faktura->created_at->format('Y')),
                    'date' => $faktura->created_at->format('Y-m-d'),
                    'amount' => $invoiceTotal,
                ]);
            }
        }

        return $result;
    }
    /**
     * Validate that previous years are closed before closing current year
     */
    private function validateSequentialYearClosure(int $year): void
    {
        // Check if any previous year is still open
        $openPreviousYear = FiscalYearClosure::where('module', 'clients')
            ->where('fiscal_year', '<', $year)
            ->whereNull('closed_at')
            ->orWhere(function($query) use ($year) {
                $query->where('module', 'clients')
                    ->where('fiscal_year', '<', $year)
                    ->whereJsonContains('summary->is_fully_closed', false);
            })
            ->orderBy('fiscal_year', 'desc')
            ->first();

        if ($openPreviousYear) {
            throw new \Exception("Cannot close year {$year}. Year {$openPreviousYear->fiscal_year} is still open.");
        }

        // Also check if there are any client entries from previous years that are not closed
        $openPreviousEntries = ClientYearEndEntry::where('fiscal_year', '<', $year)
            ->whereIn('status', ['pending', 'ready_to_close'])
            ->orderBy('fiscal_year', 'desc')
            ->first();

        if ($openPreviousEntries) {
            throw new \Exception("Cannot close year {$year}. Year {$openPreviousEntries->fiscal_year} has unclosed client entries.");
        }
    }

    /**
     * Mark all clients as ready to close for a given year
     */
    public function markAllReady(int $year): int
    {
        $count = ClientYearEndEntry::forFiscalYear($year)
            ->where('status', 'pending')
            ->update(['status' => 'ready_to_close']);

        return $count;
    }

    /**
     * Check if all clients are ready to close for a given year
     */
    public function areAllClientsReady(int $year): bool
    {
        $pendingCount = ClientYearEndEntry::forFiscalYear($year)
            ->where('status', 'pending')
            ->count();

        return $pendingCount === 0;
    }
}