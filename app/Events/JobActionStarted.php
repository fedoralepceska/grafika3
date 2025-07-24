<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobActionStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $jobId;
    public $actionId;
    public $actionName;
    public $userId;
    public $startedAt;

    public function __construct($jobId, $actionId, $actionName, $userId, $startedAt)
    {
        $this->jobId = $jobId;
        $this->actionId = $actionId;
        $this->actionName = $actionName;
        $this->userId = $userId;
        $this->startedAt = $startedAt;
    }

    public function broadcastOn()
    {
        return [
            new Channel('job-actions'),
            new PrivateChannel('user.' . $this->userId)
        ];
    }

    public function broadcastAs()
    {
        return 'job.action.started';
    }

    public function broadcastWith()
    {
        return [
            'job_id' => $this->jobId,
            'action_id' => $this->actionId,
            'action_name' => $this->actionName,
            'user_id' => $this->userId,
            'started_at' => $this->startedAt,
            'timestamp' => now()->toISOString()
        ];
    }
} 