<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricePerQuantity extends Model
{
    protected $table = 'price_per_quantity';

    protected $fillable = [
        'catalog_item_id',
        'client_id',
        'quantity_from',
        'quantity_to',
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity_from' => 'integer',
        'quantity_to' => 'integer'
    ];

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getQuantityRangeAttribute(): string
    {
        if ($this->quantity_from === null) {
            return "Up to {$this->quantity_to}";
        }
        
        if ($this->quantity_to === null) {
            return "{$this->quantity_from}+";
        }
        
        return "{$this->quantity_from} - {$this->quantity_to}";
    }
} 