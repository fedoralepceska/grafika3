<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\HistoryLog;

class LogInvoiceHistory
{
    public function handle(InvoiceCreated $event)
    {
        $action = "Invoice created!";

        HistoryLog::create([
            'invoice_id' => $event->invoice->id,
            'action' => $action,
            'performed_by' => $event->invoice->created_by
        ]);
    }
}
