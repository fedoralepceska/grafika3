<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\JobAction;
use App\Enums\MachineCut;
use App\Enums\MachinePrint;
use App\Enums\Material;
use App\Enums\MaterialSmall;
use App\Http\Controllers\Auth\PasswordController;
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
        'started_by',
        'price',
        'salePrice'
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

    public function startedByUser()
    {
        return $this->belongsTo(User::class, 'started_by');
    }


    public function large_material()
    {
        return $this->belongsTo(LargeFormatMaterial::class, 'large_material_id');
    }


    public function getTotalPriceAttribute(): float|int
    {
        // Check if the small material is set
        $smallMaterial = $this->small_material()->with('smallFormatMaterial')->first();
        $largeMaterial = $this->large_material()->first();

        if ($smallMaterial === null && $largeMaterial === null) {
            // Return a default value if no small material is set
            return 0;
        }
        $result = 0;
        if ($smallMaterial) {
            // Calculate the number of used materials.
            $formatQuantity = $smallMaterial->smallFormatMaterial->quantity; //100
            $formatPrice = $smallMaterial->smallFormatMaterial->price_per_unit; //10
            $baseQuantity = $this->quantity; // 500
            $baseCopies = $this->copies; // 50
            $materialQuantity = $smallMaterial->quantity; // 9
            $remainingQuantity = $formatQuantity - ($baseCopies / $materialQuantity); // total - used

            $smallMaterial->smallFormatMaterial->update(['quantity' => $remainingQuantity]);

            $result = ceil($baseCopies / $materialQuantity) * $formatPrice;
        }
        if ($largeMaterial) {
            $materialWidth = $largeMaterial->width;
            $materialHeight = $largeMaterial->height;
            $materialPrice = $largeMaterial->price_per_unit;
            $baseWidth = number_format($this->width / 1000, 2);
            $baseHeight = number_format($this->height / 1000, 2);
            $remainder = $materialWidth - $baseWidth;
            $remainderTotal = $remainder * $baseHeight;
            $jobTotal = $baseWidth * $baseHeight;

            $total = $remainderTotal + $jobTotal;

            $largeMaterial->update(['height' => $materialHeight - $baseHeight]);

            $result = $total * $materialPrice;
        }

        return $result;
    }


    public function scopeWithActionStatusCounts($query)
    {
        return $query->withCount(['actions as action_status_count' => function ($query) {
            $query->select(DB::raw("concat(job_action_id, '-', status) as actionstatus"))
                ->groupBy('status', 'job_action_id');
        }]);
    }
}
