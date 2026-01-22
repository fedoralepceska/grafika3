<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'comment',
        'selected_actions'
    ];

    protected $casts = [
        'selected_actions' => 'array'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}