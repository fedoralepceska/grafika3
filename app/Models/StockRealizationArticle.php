<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRealizationArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_realization_job_id',
        'article_id',
        'quantity',
        'unit_type',
        'source',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function stockRealizationJob(): BelongsTo
    {
        return $this->belongsTo(StockRealizationJob::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
