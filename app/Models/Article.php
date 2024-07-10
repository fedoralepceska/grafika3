<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'article';

    protected $fillable = [
        'code',
        'name',
        'tax_type',
        'barcode',
        'comment',
        'width',
        'height',
        'length',
        'weight',
        'color',
        'purchase_price',
        'factory_price',
        'price_1',
        'in_meters',
        'in_kilograms',
        'in_pieces'
    ];

    public function priemnica()
    {
        return $this->belongsTo(Priemnica::class); // Using the bridge table (optional)
    }
}
