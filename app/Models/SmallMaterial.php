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
        return $this->belongsTo(SmallFormatMaterial::class, 'small_format_material_id');
    }

    // Relationship with other SmallMaterials
    public function small_materials(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SmallMaterial::class, 'small_format_material_id', 'small_format_material_id');
    }

    public function format() {
        return $this->belongsTo(SmallFormatMaterial::class, 'small_format_material_id');
    }
}
