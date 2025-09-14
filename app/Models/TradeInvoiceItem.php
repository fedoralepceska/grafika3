<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'trade_invoice_id',
        'article_id',
        'quantity',
        'unit_price',
        'tax_type',
        'line_total',
        'vat_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:5',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'vat_amount' => 'decimal:2',
    ];

    public function tradeInvoice()
    {
        return $this->belongsTo(TradeInvoice::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Calculate line totals and VAT
     */
    public function calculateTotals()
    {
        $this->line_total = $this->quantity * $this->unit_price;
        $vatPercentage = TradeInvoice::calculateVatPercentage($this->tax_type);
        $this->vat_amount = $this->line_total * ($vatPercentage / 100);
        $this->save();
    }

    /**
     * Boot method to auto-calculate totals
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            if ($item->quantity && $item->unit_price) {
                $item->line_total = $item->quantity * $item->unit_price;
                $vatPercentage = TradeInvoice::calculateVatPercentage($item->tax_type);
                $item->vat_amount = $item->line_total * ($vatPercentage / 100);
            }
        });
    }
}
