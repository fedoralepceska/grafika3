<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferClient extends Model
{
    public function offer()
    {
        return $this->belongsTo(\App\Models\Offer::class);
    }

    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class);
    }
}
