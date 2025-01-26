<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingFaktura extends Model
{
    protected $table = 'incoming_faktura';

    protected $fillable = [
        'warehouse',
        'cost_type',
        'billing_type',
        'description',
        'comment',
        'amount',
        'tax',
        'client_id',
        'date',
        'incoming_number'
    ];

    protected $casts = [
        'amount' => 'float',
        'tax' => 'float',
        'cost_type' => 'integer',
        'billing_type' => 'integer',
        'client_id' => 'integer',
        'date' => 'date'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
