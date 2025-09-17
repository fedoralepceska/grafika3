<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\IndividualOrder;

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

        // When invoice becomes Completed and client is 'Физичко лице'
        $clientName = $invoice->client?->name;
        if ($originalStatus !== 'Completed' && $newStatus === 'Completed' && $clientName === 'Физичко лице') {
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
