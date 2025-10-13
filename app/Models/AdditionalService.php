<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalService extends Model
{
    use HasFactory;

    protected $fillable = [
        'faktura_id',
        'name',
        'quantity',
        'unit',
        'sale_price',
        'vat_rate',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'sale_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
    ];

    /**
     * Get the faktura that owns the additional service.
     */
    public function faktura()
    {
        return $this->belongsTo(Faktura::class);
    }

    /**
     * Calculate the total price for this service (without VAT).
     */
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->sale_price;
    }

    /**
     * Calculate the VAT amount for this service.
     */
    public function getVatAmountAttribute()
    {
        return $this->total_price * ($this->vat_rate / 100);
    }

    /**
     * Calculate the total price including VAT.
     */
    public function getTotalPriceWithVatAttribute()
    {
        return $this->total_price + $this->vat_amount;
    }
}