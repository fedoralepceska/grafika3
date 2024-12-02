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
        'actions'
    ];

    protected $casts = [
        'actions' => 'array'
    ];

    public function largeMaterial()
    {
        return $this->belongsTo(LargeFormatMaterial::class, 'large_material_id');
    }

    public function smallMaterial()
    {
        return $this->belongsTo(SmallMaterial::class, 'small_material_id');
    }

}
