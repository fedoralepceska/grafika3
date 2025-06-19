<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LargeFormatMaterial;
use App\Models\SmallMaterial;

class Article extends Model
{
    protected $table = 'article';

    protected $fillable = [
        'code',
        'name',
        'tax_type',
        'barcode',
        'comment',
        'width',
        'height',
        'length',
        'weight',
        'color',
        'purchase_price',
        'factory_price',
        'price_1',
        'in_meters',
        'in_square_meters',
        'in_kilograms',
        'in_pieces',
        'type',
        'format_type',
    ];

    public function priemnica()
    {
        return $this->belongsTo(Priemnica::class); // Using the bridge table (optional)
    }

    public function categories()
    {
        return $this->belongsToMany(ArticleCategory::class, 'article_category_article');
    }

    public function largeFormatMaterial()
    {
        return $this->hasOne(LargeFormatMaterial::class, 'article_id');
    }

    public function smallMaterial()
    {
        return $this->hasOne(SmallMaterial::class, 'article_id');
    }
}
