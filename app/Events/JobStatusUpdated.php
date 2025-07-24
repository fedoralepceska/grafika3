<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $jobId;
    public $status;
    public $userId;

    public function __construct($jobId, $status, $userId = null)
    {
        $this->jobId = $jobId;
        $this->status = $status;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        $channels = [new Channel('job-actions')];
        
        if ($this->userId) {
            $channels[] = new PrivateChannel('user.' . $this->userId);
        }
        
        return $channels;
    }

    public function broadcastAs()
    {
        return 'job.status.updated';
    }

    public function broadcastWith()
    {
        return [
            'job_id' => $this->jobId,
            'status' => $this->status,
            'timestamp' => now()->toISOString()
        ];
    }
} 