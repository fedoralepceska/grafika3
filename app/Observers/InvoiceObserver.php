<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\IndividualOrder;
use App\Models\StockRealization;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        // Only react when status changed
        if ($invoice->isDirty('status') === false) {
            return;
        }

        $originalStatus = $invoice->getOriginal('status');
        $newStatus = $invoice->status;

        // When invoice becomes Completed
        if ($originalStatus !== 'Completed' && $newStatus === 'Completed') {
            $clientName = $invoice->client?->name;
            
            // Handle Individual Orders (Физичко лице)
            if ($clientName === 'Физичко лице') {
                // Compute total from jobs' salePrice
                $invoice->loadMissing('jobs', 'client');
                $total = (float) $invoice->jobs->sum('salePrice');

                // Upsert to avoid duplicates
                IndividualOrder::updateOrCreate(
                    ['invoice_id' => $invoice->id],
                    [
                        'client_id' => $invoice->client_id,
                        'total_amount' => $total,
                    ]
                );
            }

            // Create Stock Realization record for all completed invoices
            // Check if stock realization already exists to avoid duplicates
            $existingStockRealization = StockRealization::where('invoice_id', $invoice->id)->first();
            if (!$existingStockRealization) {
                try {
                    StockRealization::createFromInvoice($invoice);
                    \Log::info('Stock realization created for completed invoice', [
                        'invoice_id' => $invoice->id,
                        'client_name' => $clientName
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to create stock realization for completed invoice', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "force deleted" event.
     */
    public function forceDeleted(Invoice $invoice): void
    {
        //
    }
}
