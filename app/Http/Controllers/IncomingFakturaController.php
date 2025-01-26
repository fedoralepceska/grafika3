<?php

namespace App\Http\Controllers;

use App\Models\IncomingFaktura;
use App\Models\Warehouse;
use App\Models\Client;
use App\Enums\CostType;
use App\Enums\BillType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class IncomingFakturaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = IncomingFaktura::with('client');

            // Search by invoice number
            if ($request->has('searchQuery') && !empty($request->input('searchQuery'))) {
                $searchQuery = $request->input('searchQuery');
                $query->where('incoming_number', 'like', "%{$searchQuery}%");
            }

            // Filter by client
            if ($request->has('filterClient') && $request->input('filterClient') !== 'All') {
                $clientId = $request->input('filterClient');
                $query->where('client_id', $clientId);
            }

            // Filter by warehouse
            if ($request->has('filterWarehouse') && !empty($request->input('filterWarehouse'))) {
                $warehouse = $request->input('filterWarehouse');
                $query->where('warehouse', $warehouse);
            }

            // Filter by cost type
            if ($request->has('filterCostType') && !empty($request->input('filterCostType'))) {
                $costType = $request->input('filterCostType');
                $query->where('cost_type', $costType);
            }

            // Filter by bill type
            if ($request->has('filterBillType') && !empty($request->input('filterBillType'))) {
                $billType = $request->input('filterBillType');
                $query->where('billing_type', $billType);
            }

            // Sort by date
            $sortOrder = $request->input('sortOrder', 'desc');
            $query->orderBy('created_at', $sortOrder);

            $incomingInvoice = $query->paginate(10);

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
                    'updated_at' => $invoice->updated_at
                ];
            });

            if ($request->wantsJson()) {
                return response()->json($incomingInvoice);
            }

            // Get warehouses from the warehouses table
            $warehouses = Warehouse::select('name')->orderBy('name')->get()->pluck('name');

            // Get clients from the clients table
            $clients = Client::select('id', 'name')->orderBy('name')->get();

            return Inertia::render('Finance/IncomingInvoice', [
                'incomingInvoice' => $incomingInvoice,
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
    public function store(Request $request)
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

            $incoming_faktura = IncomingFaktura::create($validatedData);

            Log::info('Incoming invoice created successfully', ['id' => $incoming_faktura->id]);
            return response()->json([
                'message' => 'Incoming invoice added successfully',
                'data' => $incoming_faktura
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating incoming invoice: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create incoming invoice', 
                'details' => $e->getMessage()
            ], 500);
        }
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
