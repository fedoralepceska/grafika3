<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'client_id',
        'validity_days',
        'production_start_date',
        'production_end_date',
        'price1',
        'price2',
        'price3',
        'status',
        'decline_reason',
        'contact_id'
    ];

    protected $casts = [
        'production_start_date' => 'date',
        'production_end_date' => 'date',
        'validity_days' => 'integer',
        'price1' => 'decimal:2',
        'price2' => 'decimal:2',
        'price3' => 'decimal:2',
    ];

    public function catalogItems()
    {
        return $this->belongsToMany(CatalogItem::class, 'catalog_item_offer')
                    ->withPivot('quantity', 'description')
                    ->withTimestamps();
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    public function isDeclined()
    {
        return $this->status === 'declined';
    }
}
