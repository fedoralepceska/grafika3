<?php

namespace App\Listeners;

use App\Events\JobStarted;
use App\Models\HistoryLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogJobStarted
{
    public function handle(JobStarted $event)
    {
        $file = $event->job->file;
        $started_by = $event->job->startedByUser->name;
        $action = "Job with file $file started by $started_by!";

        $performedBy = $event->job->updated_by ?? '1';

        HistoryLog::create([
            'invoice_id' => $event->invoice->id,
            'action' => $action,
            'performed_by' => $performedBy
        ]);
    }
}
