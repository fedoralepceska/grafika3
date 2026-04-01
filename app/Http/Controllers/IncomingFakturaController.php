<?php

namespace App\Http\Controllers;

use App\Models\IncomingFaktura;
use App\Models\Warehouse;
use App\Models\Client;
use App\Enums\CostType;
use App\Enums\BillType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class IncomingFakturaController extends Controller
{
    protected function applyIncomingDefaultMonthDateFilter(Request $request): void
    {
        if ($request->boolean('no_date_filter')) {
            return;
        }
        if (! $request->filled('date_from') && ! $request->filled('date_to')) {
            $request->merge([
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString(),
            ]);
        }
    }

    protected function incomingFilteredBaseQuery(Request $request): Builder
    {
        $query = IncomingFaktura::query();

        if ($request->has('searchQuery') && ! empty($request->input('searchQuery'))) {
            $searchQuery = $request->input('searchQuery');
            $query->where('incoming_number', 'like', "%{$searchQuery}%");
        }

        if ($request->has('filterClient') && $request->input('filterClient') !== 'All') {
            $clientId = $request->input('filterClient');
            $query->where('client_id', $clientId);
        }

        if ($request->has('filterWarehouse') && ! empty($request->input('filterWarehouse'))) {
            $query->where('warehouse', $request->input('filterWarehouse'));
        }

        if ($request->has('filterCostType') && ! empty($request->input('filterCostType'))) {
            $query->where('cost_type', $request->input('filterCostType'));
        }

        if ($request->has('filterBillType') && ! empty($request->input('filterBillType'))) {
            $query->where('billing_type', $request->input('filterBillType'));
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

        return $query;
    }

    protected function computeIncomingFilterTotals(Builder $filteredQuery): array
    {
        $row = (clone $filteredQuery)->selectRaw(
                            'COUNT(*) as row_count, COALESCE(SUM(amount), 0) as sum_amount, COALESCE(SUM(tax), 0) as sum_tax'
                        )->first();

        $sumAmount = (float) $row->sum_amount;
        $sumTax = (float) $row->sum_tax;
        $total = round($sumAmount + $sumTax, 2);

        return [
            'row_count' => (int) $row->row_count,
            'amount' => number_format($sumAmount, 2, '.', ','),
            'tax' => number_format($sumTax, 2, '.', ','),
            'total' => number_format($total, 2, '.', ','),
        ];
    }

    public function index(Request $request)
    {
        try {
            $this->applyIncomingDefaultMonthDateFilter($request);
            $baseQuery = $this->incomingFilteredBaseQuery($request);
            $page = (int) $request->input('page', 1);
            $filterTotalsPage1 = $page === 1
                ? $this->computeIncomingFilterTotals($baseQuery)
                : null;

            $sortOrder = $request->input('sortOrder', 'desc');
            $listQuery = (clone $baseQuery)->with('client')->orderBy('created_at', $sortOrder);

            $perPage = (int) $request->input('per_page', 10);
            $perPage = max(1, min($perPage, 200));
            $incomingInvoice = $listQuery->paginate($perPage)->withQueryString();

            $incomingInvoice->getCollection()->transform(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'incoming_number' => $invoice->incoming_number,
                    'client_id' => $invoice->client_id,
                    'client_name' => $invoice->client ? $invoice->client->name : null,
                    'warehouse' => $invoice->warehouse,
                    'cost_type' => CostType::tryFrom($invoice->cost_type)?->label(),
                    'cost_type_id' => $invoice->cost_type,
                    'billing_type' => BillType::tryFrom($invoice->billing_type)?->label(),
                    'billing_type_id' => $invoice->billing_type,
                    'description' => $invoice->description,
                    'comment' => $invoice->comment,
                    'amount' => number_format($invoice->amount, 2),
                    'tax' => number_format($invoice->tax, 2),
                    'date' => $invoice->date,
                    'created_at' => $invoice->created_at,
                    'updated_at' => $invoice->updated_at,
                    'faktura_counter' => $invoice->faktura_counter
                ];
            });

            if ($request->wantsJson()) {
                $payload = $incomingInvoice->toArray();
                if ($filterTotalsPage1 !== null) {
                    $payload['filter_totals'] = $filterTotalsPage1;
                }

                return response()->json($payload);
            }

            $warehouses = Warehouse::select('name')->orderBy('name')->get()->pluck('name');

            $clients = Client::select('id', 'name')->orderBy('name')->get();

            $filterTotalsInertia = $this->computeIncomingFilterTotals($baseQuery);

            return Inertia::render('Finance/IncomingInvoice', [
                'incomingInvoice' => $incomingInvoice,
                'filter_totals' => $filterTotalsInertia,
                'costTypes' => CostType::toArray(),
                'billTypes' => BillType::toArray(),
                'warehouses' => $warehouses,
                'clients' => $clients
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching incoming invoices: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function getNextFakturaCounter()
    {
        $counter = IncomingFaktura::getNextFakturaCounter();
        return response()->json(['counter' => $counter]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'incoming_number' => 'required|string',
            'client_id' => 'nullable|exists:clients,id',
            'warehouse' => 'nullable|string',
            'cost_type' => 'nullable|integer',
            'billing_type' => 'nullable|integer',
            'description' => 'nullable|string',
            'comment' => 'nullable|string',
            'amount' => 'required|numeric',
            'tax' => 'required|numeric',
            'date' => 'nullable|date'
        ]);

        // If billing type is фактура (2), get and set the counter
        if ($request->billing_type === 2) {
            $data['faktura_counter'] = IncomingFaktura::getNextFakturaCounter();
        }

        $incomingFaktura = IncomingFaktura::create($data);

        return response()->json($incomingFaktura);
    }

    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'incoming_number' => 'nullable|string',
                'client_id' => 'nullable|integer',
                'warehouse' => 'nullable|string',
                'cost_type' => 'nullable|integer',
                'billing_type' => 'nullable|integer',
                'description' => 'nullable|string',
                'comment' => 'nullable|string',
                'amount' => 'nullable|numeric',
                'tax' => 'nullable|numeric',
                'date' => 'nullable|date'
            ]);

            $incoming_faktura = IncomingFaktura::findOrFail($id);
            
            // Case 1: If changing from billing_type 2 to something else, remove the counter
            if ($incoming_faktura->billing_type === 2 && $request->billing_type !== 2) {
                $validatedData['faktura_counter'] = null;
            }
            
            // Case 2: If changing to billing_type 2, assign a new counter
            if ($incoming_faktura->billing_type !== 2 && $request->billing_type === 2) {
                $validatedData['faktura_counter'] = IncomingFaktura::getNextFakturaCounter();
            }

            $incoming_faktura->update($validatedData);

            Log::info('Incoming invoice updated successfully', ['id' => $id]);
            return response()->json([
                'message' => 'Incoming invoice updated successfully',
                'data' => $incoming_faktura
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating incoming invoice: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update incoming invoice',
                'details' => $e->getMessage()
            ], 500);
        }
    }

}
