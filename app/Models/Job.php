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
use App\Models\Dorabotka;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'file',
        'originalFile',
        'width',
        'height',
        'quantity',
        'copies',
        'machinePrint',
        'machineCut',
        'large_material_id',
        'small_material_id',
        'shippingInfo',
        'status',
        'name'
    ];

    protected $with = ['actions'];

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
        return $this->belongsToMany(JobAction::class, 'job_job_action', 'job_id', 'job_action_id')
            ->withPivot(['status', 'quantity']);
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
        $smallMaterial = SmallMaterial::with('article')->find($this->small_material_id);
        $largeMaterial = LargeFormatMaterial::with('article')->find($this->large_material_id);
        $price = 0;
        $job = Job::with('actions')->find($this->id)->toArray();
        foreach ($job['actions'] as $action) {
            if ($action['quantity']) {
                if (isset($smallMaterial)) {
                    $price = $price + ($action['quantity']*$smallMaterial->article->price_1);
                }
                if (isset($largeMaterial)) {
                    $price = $price + ($action['quantity']*$largeMaterial->article->price_1);
                }
            }
        }
        return $price;
    }

    public function scopeWithActionStatusCounts($query)
    {
        return $query->withCount(['actions as action_status_count' => function ($query) {
            $query->select(DB::raw("concat(job_action_id, '-', status) as actionstatus"))
                ->groupBy('status', 'job_action_id');
        }]);
    }
}
