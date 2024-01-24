<?php

namespace App\Listeners;

use App\Events\JobEnded;
use App\Events\JobStarted;
use App\Models\HistoryLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogJobEnded
{
    public function handle(JobEnded $event)
    {
        $file = $event->job->file;
        $action = "Job with file $file ended!";

        $performedBy = $event->job->updated_by ?? '1';

        HistoryLog::create([
            'invoice_id' => $event->invoice->id,
            'action' => $action,
            'performed_by' => $performedBy
        ]);
    }
}
