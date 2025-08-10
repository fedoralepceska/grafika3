<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function catalogItems(): BelongsToMany
    {
        return $this->belongsToMany(CatalogItem::class, 'catalog_item_subcategory')
            ->withTimestamps();
    }
}
