<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\JobAction;
use App\Enums\MachineCut;
use App\Enums\MachinePrint;
use App\Enums\Material;
use App\Enums\MaterialSmall;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'width',
        'height',
        'file',
        'originalFile',
        'estimatedTime',
        'shippingInfo',
        'materials',
        'materialsSmall',
        'machineCut',
        'machinePrint',
        'status',
        'quantity',
        'copies',
        'price'
    ];

    protected $attributes = [
        'estimatedTime' => 0, // Set a default value for estimatedTime
        'shippingInfo' => '', // Set a default value for shippingInfo
        'status' => 'Not started yet', // Set a default value for status
    ];

    protected array $enumCasts = [
        'status' => InvoiceStatus::class,
    ];

    public function actions(): BelongsToMany
    {
        return $this->belongsToMany(JobAction::class)->withPivot('status');
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class);
    }

    public function small_material()
    {
        return $this->belongsTo(SmallMaterial::class, 'small_material_id');
    }

    public function getTotalPriceAttribute(): float|int
    {
        // Ensure the small material and its related small format material are loaded.
        $smallMaterial = $this->small_material()->with('smallFormatMaterial')->firstOrFail();
        // Calculate the number of used materials.
        $baseQuantity = $this->quantity;
        $materialQuantity = $smallMaterial->quantity;
        $usedMaterialResult = fdiv($baseQuantity, $materialQuantity);
        // Determine the remainder to adjust the count of used materials.
        $remainder = $baseQuantity % $materialQuantity;
        if($remainder > 0) {
            $usedMaterialResult += ($remainder <= ($materialQuantity * 0.5)) ? 0.5 : 1;
        }

        // Calculate the total price.
        $PricePerMaterial = $smallMaterial->smallFormatMaterial->price_per_unit / $materialQuantity;
        $totalPrice = $PricePerMaterial * $usedMaterialResult * $this->copies;
        // Update the remaining quantity of small format materials.
        $usedFormats = $usedMaterialResult * $this->copies;
        $smallMaterial->smallFormatMaterial->decrement('quantity', $usedFormats);
        $this -> price = 2.2;
        return $totalPrice;
    }

}
