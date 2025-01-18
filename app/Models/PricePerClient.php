<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricePerClient extends Model
{
    protected $table = 'price_per_client';

    protected $fillable = [
        'catalog_item_id',
        'client_id',
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
} 