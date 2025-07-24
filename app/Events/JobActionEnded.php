<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobActionEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $jobId;
    public $actionId;
    public $actionName;
    public $userId;
    public $endedAt;
    public $timeSpent;

    public function __construct($jobId, $actionId, $actionName, $userId, $endedAt, $timeSpent)
    {
        $this->jobId = $jobId;
        $this->actionId = $actionId;
        $this->actionName = $actionName;
        $this->userId = $userId;
        $this->endedAt = $endedAt;
        $this->timeSpent = $timeSpent;
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
        return 'job.action.ended';
    }

    public function broadcastWith()
    {
        return [
            'job_id' => $this->jobId,
            'action_id' => $this->actionId,
            'action_name' => $this->actionName,
            'user_id' => $this->userId,
            'ended_at' => $this->endedAt,
            'time_spent' => $this->timeSpent,
            'timestamp' => now()->toISOString()
        ];
    }
} 