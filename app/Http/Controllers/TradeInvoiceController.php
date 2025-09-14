<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Client;
use App\Models\ClientCardStatement;
use App\Models\TradeInvoice;
use App\Models\TradeInvoiceItem;
use App\Models\TradeWarehouseInventory;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PDF;
use Milon\Barcode\DNS1D;

class TradeInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $invoicesQuery = TradeInvoice::with(['client', 'warehouse', 'createdBy', 'items.article'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('client_id') && $request->client_id !== 'All') {
            $invoicesQuery->where('client_id', $request->client_id);
        }

        if ($request->filled('warehouse_id') && $request->warehouse_id !== 'All') {
            $invoicesQuery->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('status') && $request->status !== 'All') {
            $invoicesQuery->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $invoicesQuery->whereDate('invoice_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $invoicesQuery->whereDate('invoice_date', '<=', $request->to_date);
        }

        $invoices = $invoicesQuery->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($invoices);
        }

        return Inertia::render('TradeInvoice/Index', [
            'invoices' => $invoices,
            'clients' => Client::all(),
            'warehouses' => Warehouse::getTradeWarehouses(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tradeWarehouses = Warehouse::getTradeWarehouses();
        
        return Inertia::render('TradeInvoice/Create', [
            'clients' => Client::all(),
            'warehouses' => $tradeWarehouses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.article_id' => 'required|exists:article,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        
        try {
            // Create the trade invoice
            $invoice = TradeInvoice::create([
                'invoice_number' => TradeInvoice::generateInvoiceNumber(),
                'client_id' => $request->client_id,
                'warehouse_id' => $request->warehouse_id,
                'invoice_date' => $request->invoice_date,
                'notes' => $request->notes,
                'status' => 'draft',
                'created_by' => Auth::id(),
                'subtotal' => 0,
                'vat_amount' => 0,
                'total_amount' => 0,
            ]);

            // Process each item
            foreach ($request->items as $itemData) {
                $article = Article::findOrFail($itemData['article_id']);
                
                // Check if sufficient stock is available
                if (!TradeWarehouseInventory::hasStock($article->id, $request->warehouse_id, $itemData['quantity'])) {
                    throw new \Exception("Insufficient stock for article: {$article->name}");
                }

                // Create invoice item
                $item = TradeInvoiceItem::create([
                    'trade_invoice_id' => $invoice->id,
                    'article_id' => $article->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'] ?? $article->price_1,
                    'tax_type' => $article->tax_type,
                ]);

                // Deduct from trade warehouse inventory
                TradeWarehouseInventory::removeStock($article->id, $request->warehouse_id, $itemData['quantity']);
            }

            // Recalculate totals
            $invoice->recalculateTotals();

            // Client card statement will automatically show trade invoices 
            // when status is 'sent' or 'paid'

            DB::commit();

            return response()->json([
                'message' => 'Trade invoice created successfully',
                'invoice' => $invoice->load(['client', 'warehouse', 'items.article'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $invoice = TradeInvoice::with(['client', 'warehouse', 'items.article', 'createdBy'])
            ->findOrFail($id);

        return Inertia::render('TradeInvoice/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $invoice = TradeInvoice::with(['client', 'warehouse', 'items.article'])
            ->findOrFail($id);

        $tradeWarehouses = Warehouse::getTradeWarehouses();

        return Inertia::render('TradeInvoice/Edit', [
            'invoice' => $invoice,
            'clients' => Client::all(),
            'warehouses' => $tradeWarehouses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.article_id' => 'required|exists:article,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        
        try {
            $invoice = TradeInvoice::findOrFail($id);
            
            // Restore stock for existing items
            foreach ($invoice->items as $existingItem) {
                TradeWarehouseInventory::addStock(
                    $existingItem->article_id,
                    $invoice->warehouse_id,
                    $existingItem->quantity
                );
            }

            // Delete existing items
            $invoice->items()->delete();

            // Update invoice basic info
            $invoice->update([
                'client_id' => $request->client_id,
                'warehouse_id' => $request->warehouse_id,
                'invoice_date' => $request->invoice_date,
                'notes' => $request->notes,
            ]);

            // Process new items
            foreach ($request->items as $itemData) {
                $article = Article::findOrFail($itemData['article_id']);
                
                // Check if sufficient stock is available
                if (!TradeWarehouseInventory::hasStock($article->id, $request->warehouse_id, $itemData['quantity'])) {
                    throw new \Exception("Insufficient stock for article: {$article->name}");
                }

                // Create invoice item
                TradeInvoiceItem::create([
                    'trade_invoice_id' => $invoice->id,
                    'article_id' => $article->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'] ?? $article->price_1,
                    'tax_type' => $article->tax_type,
                ]);

                // Deduct from trade warehouse inventory
                TradeWarehouseInventory::removeStock($article->id, $request->warehouse_id, $itemData['quantity']);
            }

            // Recalculate totals
            $invoice->recalculateTotals();

            DB::commit();

            return response()->json([
                'message' => 'Trade invoice updated successfully',
                'invoice' => $invoice->load(['client', 'warehouse', 'items.article'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $invoice = TradeInvoice::findOrFail($id);
            
            // Restore stock for all items
            foreach ($invoice->items as $item) {
                TradeWarehouseInventory::addStock(
                    $item->article_id,
                    $invoice->warehouse_id,
                    $item->quantity
                );
            }

            $invoice->delete();

            DB::commit();

            return response()->json(['message' => 'Trade invoice deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get available articles in trade warehouse
     */
    public function getAvailableArticles($warehouseId)
    {
        $articles = TradeWarehouseInventory::with('article')
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function ($inventory) {
                return [
                    'id' => $inventory->article->id,
                    'code' => $inventory->article->code,
                    'name' => $inventory->article->name,
                    'available_quantity' => $inventory->quantity,
                    'purchase_price' => $inventory->purchase_price,
                    'selling_price' => $inventory->selling_price ?? $inventory->article->price_1,
                    'tax_type' => $inventory->article->tax_type,
                ];
            });

        return response()->json($articles);
    }

    /**
     * Update invoice status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,paid,cancelled'
        ]);

        DB::beginTransaction();
        
        try {
            $invoice = TradeInvoice::findOrFail($id);
            $oldStatus = $invoice->status;
            
            $invoice->update(['status' => $request->status]);

            // Client card statement will automatically reflect trade invoices 
            // when status is 'sent' or 'paid' through the ClientCardStatementController

            DB::commit();

            return response()->json([
                'message' => 'Invoice status updated successfully',
                'invoice' => $invoice
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Generate PDF for trade invoice
     */
    public function generatePdf($id)
    {
        $invoice = TradeInvoice::with(['client', 'client.clientCardStatement', 'warehouse', 'items.article', 'createdBy'])
            ->findOrFail($id);

        $dns1d = new DNS1D();
        $barcodeString = $invoice->id . '-' . date('m-Y', strtotime($invoice->invoice_date));
        $barcodeImage = base64_encode($dns1d->getBarcodePNG($barcodeString, 'C128'));

        // Transform trade invoice data to match outgoing_invoice format
        $transformedInvoice = [
            'id' => $invoice->id,
            'invoice_title' => 'Trade Invoice #' . $invoice->invoice_number,
            'client' => $invoice->client,
            'user' => $invoice->createdBy,
            'start_date' => $invoice->invoice_date,
            'end_date' => $invoice->invoice_date,
            'status' => ucfirst($invoice->status),
            'jobs' => [], // Empty jobs array since trade invoices don't have jobs
            'barcodeImage' => $barcodeImage,
            'totalSalePrice' => $invoice->subtotal,
            'taxRate' => 18.00, // Default VAT rate
            'priceWithTax' => $invoice->total_amount,
            'taxAmount' => $invoice->vat_amount,
            'copies' => 1, // Default for trade invoices
            'trade_items' => $invoice->items->map(function ($item) {
                return [
                    'article_id' => $item->article->id,
                    'article_name' => $item->article->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->line_total,
                    'vat_rate' => TradeInvoice::calculateVatPercentage($item->tax_type),
                    'vat_amount' => $item->vat_amount
                ];
            }),
            'comment' => $invoice->notes ?? ''
        ];

        try {
            // Use the same template as outgoing invoices
            $pdf = PDF::loadView('invoices.outgoing_invoice', [
                'invoices' => [$transformedInvoice], // Wrap in array as expected by template
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'chroot' => storage_path('fonts'),
                'dpi' => 150,
            ]);

            $filename = 'trade_invoice_' . $invoice->invoice_number . '.pdf';
            
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }


}
