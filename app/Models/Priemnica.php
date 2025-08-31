<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    public function client() {
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
