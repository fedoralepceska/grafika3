<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price1', 'price2', 'price3'];

    public function catalogItems()
    {
        return $this->belongsToMany(CatalogItem::class, 'catalog_item_offer', 'offer_id', 'catalog_item_id');
    }
}
