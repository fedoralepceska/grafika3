<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dorabotka extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'isMaterialized',
    ];
}
