<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'machinePrint',
        'machineCut',
        'large_material_id',
        'small_material_id',
        'large_material_category_id',
        'small_material_category_id',
        'quantity',
        'copies',
        'actions',
        'is_for_offer',
        'is_for_sales',
        'category',
        'file',
        'template_file',
        'price',
        'cost_price',
        'subcategory_id',
        'should_ask_questions'
    ];

    protected $casts = [
        'is_for_offer' => 'boolean',
        'is_for_sales' => 'boolean',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'actions' => 'array'
    ];

    protected $appends = [
        'price_per_client_count',
        'price_per_quantity_count',
    ];

    public const CATEGORIES = [
        'material',
        'article',
        'small_format',
    ];

    // Original relationships
    public function largeMaterial()
    {
        return $this->belongsTo(LargeFormatMaterial::class, 'large_material_id');
    }

    public function smallMaterial()
    {
        return $this->belongsTo(SmallMaterial::class, 'small_material_id');
    }

    // New category relationships
    public function largeMaterialCategory()
    {
        return $this->belongsTo(ArticleCategory::class, 'large_material_category_id');
    }

    public function smallMaterialCategory()
    {
        return $this->belongsTo(ArticleCategory::class, 'small_material_category_id');
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'catalog_item_offer');
    }

    // New pricing relationships
    public function clientPrices()
    {
        return $this->hasMany(PricePerClient::class);
    }

    public function quantityPrices()
    {
        return $this->hasMany(PricePerQuantity::class);
    }

    // Original category methods
    public function setCategoryAttribute($value): void
    {
        if (!in_array($value, self::CATEGORIES, true)) {
            throw new \InvalidArgumentException("Invalid category: {$value}");
        }
        $this->attributes['category'] = $value;
    }

    public function isMaterial(): bool
    {
        return $this->category === 'material';
    }

    public function isArticle(): bool
    {
        return $this->category === 'article';
    }

    public function isSmallFormat(): bool
    {
        return $this->category === 'small_format';
    }

    // New pricing accessors and methods
    public function getPricePerClientCountAttribute()
    {
        return $this->clientPrices()->count();
    }

    public function getPricePerQuantityCountAttribute()
    {
        return $this->quantityPrices()->count();
    }

    public function getEffectivePrice($clientId, $quantity = 1)
    {
        // First, check for a client-specific price
        $clientPrice = $this->clientPrices()
            ->where('client_id', $clientId)
            ->first();

        if ($clientPrice) {
            return $clientPrice->price;
        }

        // If no client-specific price, check for quantity-based price
        $quantityPrice = $this->getQuantityBasedPrice($clientId, $quantity);

        if ($quantityPrice) {
            return $quantityPrice;
        }

        // If no special pricing applies, return the default price
        return $this->price;
    }

    public function getQuantityBasedPrice($clientId, $quantity)
    {
        return $this->quantityPrices()
            ->where('client_id', $clientId)
            ->where(function ($query) use ($quantity) {
                $query->where(function ($q) use ($quantity) {
                    // Case 1: quantity falls between from and to
                    $q->whereNotNull('quantity_from')
                        ->whereNotNull('quantity_to')
                        ->where('quantity_from', '<=', $quantity)
                        ->where('quantity_to', '>=', $quantity);
                })->orWhere(function ($q) use ($quantity) {
                    // Case 2: quantity is above 'from' with no upper limit
                    $q->whereNotNull('quantity_from')
                        ->whereNull('quantity_to')
                        ->where('quantity_from', '<=', $quantity);
                })->orWhere(function ($q) use ($quantity) {
                    // Case 3: quantity is below 'to' with no lower limit
                    $q->whereNull('quantity_from')
                        ->whereNotNull('quantity_to')
                        ->where('quantity_to', '>=', $quantity);
                });
            })
            ->value('price');
    }

    public function hasCustomPricing($clientId)
    {
        return $this->clientPrices()
            ->where('client_id', $clientId)
            ->exists() || $this->quantityPrices()
            ->where('client_id', $clientId)
            ->exists();
    }

    // Add new relationship for articles
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'catalog_item_articles')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    // Method to calculate and update cost price
    public function calculateCostPrice()
    {
        $totalCost = 0;

        // Calculate cost from articles
        foreach ($this->articles as $article) {
            $totalCost += ($article->purchase_price ?? 0) * $article->pivot->quantity;
        }

        // Update the cost_price
        $this->cost_price = $totalCost;
        $this->save();

        return $totalCost;
    }

    // Add this new relationship method
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }
}
