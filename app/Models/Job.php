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

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'width',
        'height',
        'file',
        'estimatedTime',
        'shippingInfo',
        'materials',
        'materialsSmall',
        'machineCut',
        'machinePrint',
        'status',
        'quantity',
        'copies'
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
}
