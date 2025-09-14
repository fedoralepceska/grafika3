<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FakturaTradeItem extends Model
{
    protected $fillable = [
        'faktura_id',
        'article_id', 
        'quantity',
        'unit_price',
        'total_price',
        'vat_rate',
        'vat_amount'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2'
    ];

    public function faktura()
    {
        return $this->belongsTo(Faktura::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}