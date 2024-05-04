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
        'client_id'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
