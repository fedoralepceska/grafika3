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

    public function getTotalPriceAttribute(){
        $usedMaterialRemainder = $this -> materialsSmall -> quantity / ($this -> materialsSmall -> quantity % 1);
        $usedMaterialResult = floor( $this -> smallMaterial -> quantity / $this -> materialsSmall -> quantity);
        if($usedMaterialRemainder < 0.5){
            $usedMaterialRemainder = 0.5;
        }elseif ($usedMaterialRemainder > 0.5){
            $usedMaterialRemainder = 1;
        }
        $usedMaterialResult = $usedMaterialResult + $usedMaterialRemainder;
        $usedMaterialsForJob = $usedMaterialResult *  $this -> materialsSmall -> quantity;
        $PricePerMaterial = $this -> materialsSmall -> smallFormatMaterial -> price_per_unit / $this -> materialsSmall -> quantity;
        $totalPrice = $PricePerMaterial * $usedMaterialsForJob * $this -> copies;
        $usedFormats = $usedMaterialResult * $this -> copies;
        $this -> materialsSmall -> smallFormatMaterial -> quantity = $this -> materialsSmall -> smallFormatMaterial -> quantity - $usedFormats;
    }
}
