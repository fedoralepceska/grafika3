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
use Illuminate\Support\Facades\DB;

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
        // Check if the small material is set
        $smallMaterial = $this->small_material()->with('smallFormatMaterial')->first();

        if ($smallMaterial === null) {
            // Return a default value if no small material is set
            return 0;
        }

        // Calculate the number of used materials.

        $baseQuantity = $this->quantity;
        $materialQuantity = $smallMaterial->quantity;
        $usedMaterialResult = fdiv($baseQuantity, $materialQuantity);

        // Determine the remainder to adjust the count of used materials.
        $remainder = $baseQuantity % $materialQuantity;
        if ($remainder > 0) {
            $usedMaterialResult += ($remainder <= ($materialQuantity * 0.05)) ? 1 : 1;
        }

        // Calculate the total price.
        $PricePerMaterial = $smallMaterial->smallFormatMaterial->price_per_unit / $materialQuantity;
        $totalPrice = $PricePerMaterial * $usedMaterialResult * $this->copies;

        // Update the remaining quantity of small format materials.
        $usedFormats = $usedMaterialResult * $this->copies;

        // Check if there is enough quantity before decrementing
        $remainingQuantity = max(0, $smallMaterial->smallFormatMaterial->quantity - $usedFormats);

        // Update the quantity only if there's enough remaining quantity
        $smallMaterial->smallFormatMaterial->update(['quantity' => $remainingQuantity]);


        return $totalPrice;
    }


    public function scopeWithActionStatusCounts($query)
    {
        return $query->withCount(['actions as action_status_count' => function ($query) {
            $query->select(DB::raw("concat(job_action_id, '-', status) as actionstatus"))
                ->groupBy('status', 'job_action_id');
        }]);
    }
}
