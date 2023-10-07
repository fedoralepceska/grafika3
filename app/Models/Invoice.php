<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
        'client',
        'invoice_title',
        'comment',
        'status',
    ];

    protected $enumCasts = [
        'status' => InvoiceStatus::class,
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];
}
