<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class OtherMaterial extends Model
{
    use HasFactory;

    protected $table = 'other_material';

    protected $fillable = [
        'name',
        'quantity',
        'width',
        'height',
        'price_per_unit',
        'article_id'
    ];

    public function article(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
