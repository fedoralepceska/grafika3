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

    public function priemnicaArticles()
    {
        return $this->belongsToMany(Priemnica::class, 'priemnica_articles')->withPivot('quantity');
    }

    public function catalogItemArticles()
    {
        return $this->belongsToMany(CatalogItem::class, 'catalog_item_articles')->withPivot('quantity');
    }

    /**
     * Calculate current available stock for this article
     */
    public function getCurrentStock()
    {
        // For material-type articles, check the corresponding material table
        if ($this->format_type == 1 && $this->smallMaterial) {
            return $this->smallMaterial->quantity ?? 0;
        }
        
        if ($this->format_type == 2 && $this->largeFormatMaterial) {
            return $this->largeFormatMaterial->quantity ?? 0;
        }
        
        if ($this->format_type == 3) {
            $otherMaterial = \App\Models\OtherMaterial::where('article_id', $this->id)->first();
            return $otherMaterial ? $otherMaterial->quantity : 0;
        }

        // For regular product/service articles, calculate from priemnica minus consumption
        $received = $this->priemnicaArticles()->sum('priemnica_articles.quantity');
        $consumed = $this->catalogItemArticles()->sum('catalog_item_articles.quantity');
        
        return max(0, $received - $consumed);
    }

    /**
     * Check if article has sufficient stock for the required quantity
     */
    public function hasStock($requiredQuantity = 1)
    {
        return $this->getCurrentStock() >= $requiredQuantity;
    }
}
