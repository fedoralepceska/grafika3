<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobAction extends Model
{
    protected $fillable = [
        'name'
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
}
