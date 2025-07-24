<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = ['question', 'order', 'active'];

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('order');
    }

    public function catalogItems()
    {
        return $this->belongsToMany(CatalogItem::class, 'catalog_item_questions');
    }
} 