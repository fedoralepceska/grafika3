<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmallFormatMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'quantity',
        'price_per_unit',
    ];



}
