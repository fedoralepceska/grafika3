<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LargeFormatMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'width',
        'height',
        'quantity',
        'price_per_unit',
    ];
}
