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
        'status'
    ];

    protected array $enumCasts = [
        'status' => InvoiceStatus::class,
        'materials' => Material::class,
        'materialsSmall' => MaterialSmall::class,
        'machineCut' => MachineCut::class,
        'machinePrint' => MachinePrint::class,
    ];

    public function jobActions(): BelongsToMany
    {
        return $this->belongsToMany(JobAction::class)->withPivot('status');
    }
}
