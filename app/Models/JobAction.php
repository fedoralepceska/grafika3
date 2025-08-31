<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class JobAction extends Model
{
    protected $fillable = [
        'name',
        'status',
        'hasNote',
        'quantity',
        'started_at',
        'ended_at',
        'started_by'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_job_action')
            ->withPivot(['status', 'quantity']);
    }

    public function catalogItems(): BelongsToMany
    {
        return $this->belongsToMany(CatalogItem::class, 'catalog_item_job_action')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function startAction(int $userId): void
    {
        $this->update([
            'started_at' => now(),
            'started_by' => $userId,
            'status' => 'In progress'
        ]);
    }

    public function endAction(): void
    {
        $this->update([
            'ended_at' => now(),
            'status' => 'Completed'
        ]);
    }

    public function getDurationInSeconds(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->ended_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    public function getFormattedDuration(): string
    {
        $seconds = $this->getDurationInSeconds();
        if ($seconds === null) {
            return '0:00:00';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }

    public function isAbandoned(): bool
    {
        if (!$this->isInProgress() || !$this->started_at) {
            return false;
        }

        return $this->started_at->diffInHours(now()) > 24;
    }

    public function isInProgress(): bool
    {
        return $this->started_at && !$this->ended_at;
    }

    public function isCompleted(): bool
    {
        return $this->started_at && $this->ended_at;
    }

    public function isNotStarted(): bool
    {
        return !$this->started_at;
    }

    /**
     * Get started_at as ISO string, handling both Carbon instances and strings
     */
    public function getStartedAtIso(): ?string
    {
        if (!$this->started_at) {
            return null;
        }
        
        return is_string($this->started_at) ? $this->started_at : $this->started_at->toISOString();
    }

    /**
     * Get ended_at as ISO string, handling both Carbon instances and strings
     */
    public function getEndedAtIso(): ?string
    {
        if (!$this->ended_at) {
            return null;
        }
        
        return is_string($this->ended_at) ? $this->ended_at : $this->ended_at->toISOString();
    }
}
