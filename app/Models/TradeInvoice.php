<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'client_id',
        'warehouse_id',
        'invoice_date',
        'subtotal',
        'vat_amount',
        'total_amount',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(TradeInvoiceItem::class);
    }

    /**
     * Calculate VAT percentage based on tax type
     */
    public static function calculateVatPercentage($taxType)
    {
        return match ($taxType) {
            1 => 18,
            2 => 5,
            3 => 10,
            default => 0,
        };
    }

    /**
     * Recalculate totals based on items
     */
    public function recalculateTotals()
    {
        $subtotal = 0;
        $vatAmount = 0;

        foreach ($this->items as $item) {
            $subtotal += $item->line_total;
            $vatAmount += $item->vat_amount;
        }

        $this->subtotal = $subtotal;
        $this->vat_amount = $vatAmount;
        $this->total_amount = $subtotal + $vatAmount;
        $this->save();
    }

    /**
     * Generate next invoice number
     */
    public static function generateInvoiceNumber()
    {
        $year = date('Y');
        $lastInvoice = self::where('invoice_number', 'like', "TI-{$year}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "TI-{$year}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
