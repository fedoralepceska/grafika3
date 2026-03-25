<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'address',
        'city'
    ];

    public function contacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function clientCardStatement(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClientCardStatement::class);
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'offer_client')
            ->withPivot('is_accepted', 'description')
            ->withTimestamps();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function fakturas(): HasMany
    {
        return $this->hasMany(Faktura::class);
    }

    public function priemnice(): HasMany
    {
        return $this->hasMany(Priemnica::class);
    }

    public function individualOrders(): HasMany
    {
        return $this->hasMany(IndividualOrder::class);
    }

    public function tradeInvoices(): HasMany
    {
        return $this->hasMany(TradeInvoice::class);
    }

    public function stockRealizations(): HasMany
    {
        return $this->hasMany(StockRealization::class);
    }

    public function incomingFakturas(): HasMany
    {
        return $this->hasMany(IncomingFaktura::class);
    }
}
