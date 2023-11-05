<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\HistoryLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogInvoiceHistory
{
    public function handle(InvoiceCreated $event)
    {
        $action = "Invoice #{$event->invoice->id} with name {$event->invoice->invoice_title}, was created by user {$event->invoice->user->name} at " . now()->toDateTimeString();

        HistoryLog::create([
            'invoice_id' => $event->invoice->id,
            'action' => $action,
            'performed_by' => $event->invoice->created_by
        ]);
    }
}
