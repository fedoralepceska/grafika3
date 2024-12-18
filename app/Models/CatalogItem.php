<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItem extends Model
{
    protected $fillable = [
        'name',
        'machinePrint',
        'machineCut',
        'large_material_id',
        'small_material_id',
        'quantity',
        'copies',
        'actions',
        'is_for_offer',
        'is_for_sales',
        'category',
        'file'
    ];

    protected $casts = [
        'actions' => 'array'
    ];

    public const CATEGORIES = [
        'material',
        'article',
        'small_format',
    ];

    public function largeMaterial()
    {
        return $this->belongsTo(LargeFormatMaterial::class, 'large_material_id');
    }

    public function smallMaterial()
    {
        return $this->belongsTo(SmallMaterial::class, 'small_material_id');
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'catalog_item_offer');
    }

    public function setCategoryAttribute($value): void
    {
        if (!in_array($value, self::CATEGORIES, true)) {
            throw new \InvalidArgumentException("Invalid category: {$value}");
        }
        $this->attributes['category'] = $value;
    }

    public function isMaterial(): bool
    {
        return $this->category === 'material';
    }

    public function isArticle(): bool
    {
        return $this->category === 'article';
    }

    public function isSmallFormat(): bool
    {
        return $this->category === 'small_format';
    }
}
