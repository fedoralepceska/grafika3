<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Priemnica extends Model
{
    use HasFactory;

    protected $table = 'priemnica';

    protected $fillable = [
        'warehouse',
        'client_id',
        'article_id',
        'quantity',
        'comment',
        'receipt_number',
        'fiscal_year',
    ];

    /**
     * Boot method to auto-generate receipt_number on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($priemnica) {
            if (empty($priemnica->receipt_number)) {
                $priemnica->generateReceiptNumber();
            }
        });
    }

    /**
     * Generate the next sequential receipt number for the current fiscal year
     */
    public function generateReceiptNumber(): void
    {
        $fiscalYear = $this->fiscal_year ?? (int) date('Y');
        $this->fiscal_year = $fiscalYear;

        $maxNumber = DB::table('priemnica')
            ->where('fiscal_year', $fiscalYear)
            ->max('receipt_number') ?? 0;

        $this->receipt_number = $maxNumber + 1;
    }

    /**
     * Get the formatted receipt number (e.g., "5/2026")
     */
    public function getFormattedReceiptNumberAttribute(): string
    {
        return $this->receipt_number . '/' . $this->fiscal_year;
    }

    /**
     * Scope to filter by fiscal year
     */
    public function scopeForFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'priemnica_articles')->withPivot('quantity', 'custom_price', 'custom_tax_type');
    }

    public static function calculateVatPercentage($vatType)
    {
        return match ($vatType) {
            '1' => 18,
            '2' => 5,
            '3' => 10,
            default => 0,
        };
    }

    public function getCalculatedTotals()
    {
        $totals = [
            'subtotal' => 0,
            'vat_amount' => 0,
            'total' => 0
        ];

        foreach ($this->articles as $article) {
            $price = $article->purchase_price * $article->pivot->quantity;
            $vatPercentage = self::calculateVatPercentage($article->vat);
            $vatAmount = $price * ($vatPercentage / 100);

            $totals['subtotal'] += $price;
            $totals['vat_amount'] += $vatAmount;
            $totals['total'] += $price + $vatAmount;
        }

        return $totals;
    }
}
