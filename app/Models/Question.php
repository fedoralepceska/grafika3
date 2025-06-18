<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = ['question', 'default_answer', 'order', 'active'];

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('order');
    }
} 