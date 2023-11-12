<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class SmallMaterial extends Model
{
    use HasFactory;

    protected $table = 'small_material';

    protected $fillable = [
        'name',
        'quantity',
        'width',
        'height',
        'small_format_material_id',
    ];

    public function smallFormatMaterial(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SmallFormatMaterial::class);
    }

    // Relationship with other SmallMaterials
    public function small_materials(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SmallMaterial::class, 'small_format_material_id', 'small_format_material_id');
    }

    // price for job
    public function getTotalPriceAttribute(Job $job)
    {
        // Calculate total quantity needed for small materials
        $totalQuantity = $job->copies * $this->quantity;

        // Calculate total number of formats needed
        $totalFormats = ceil($totalQuantity / $this->smallMaterialFormat->quantity);

        // Calculate total price
        $totalPrice = $totalFormats * $this->smallMaterialFormat->price_per_unit;

        // Add 5% to the total price
        $finalPrice = $totalPrice * 1.05;

        return $finalPrice;
    }
}
