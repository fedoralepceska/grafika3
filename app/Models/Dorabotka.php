<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dorabotka extends Model
{
    use HasFactory;

    protected $table = 'dorabotka';

    protected $fillable=[
        'name',
        'isMaterialized',
        'material_type',
        'material_id',
        'small_material_id',
        'large_material_id'
    ];

    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_job_action', 'job_action_id', 'job_id')
            ->withPivot(['status', 'quantity']);
    }
    public function smallMaterial(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SmallMaterial::class, 'small_material_id');
    }

    public function largeFormatMaterial(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LargeFormatMaterial::class, 'large_material_id');
    }
}
