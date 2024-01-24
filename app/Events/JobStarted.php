<?php

namespace App\Events;

use App\Models\Invoice;
use App\Models\Job;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $job;
    public $invoice;

    /**
     * Create a new event instance.
     */
    public function __construct(Job $job, Invoice $invoice)
    {
        $this->job = $job;
        $this->invoice = $invoice;
    }
}
