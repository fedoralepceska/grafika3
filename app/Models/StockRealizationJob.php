<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockRealizationJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_realization_id',
        'job_id',
        'name',
        'quantity',
        'copies',
        'total_area_m2',
        'width',
        'height',
        'dimensions_breakdown',
        'small_material_id',
        'large_material_id',
        'catalog_item_id',
    ];

    protected $casts = [
        'dimensions_breakdown' => 'array',
        'total_area_m2' => 'decimal:4',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
    ];

    public function stockRealization(): BelongsTo
    {
        return $this->belongsTo(StockRealization::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function smallMaterial(): BelongsTo
    {
        return $this->belongsTo(SmallMaterial::class);
    }

    public function largeMaterial(): BelongsTo
    {
        return $this->belongsTo(LargeFormatMaterial::class, 'large_material_id');
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(StockRealizationArticle::class);
    }
}
