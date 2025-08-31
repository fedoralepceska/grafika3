<?php

namespace App\Models;

use App\Models\JobAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerAnalytics extends Model
{
    use HasFactory;

    protected $table = 'workers_analytics';

    protected $fillable = ['invoice_id', 'action_id', 'user_id', 'job_id', 'time_spent'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function action()
    {
        return $this->belongsTo(JobAction::class, 'action_id');
    }
}
