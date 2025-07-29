<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogItem extends Model
{
    use HasFactory, SoftDeletes;

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
        'should_ask_questions',
        'by_quantity',
        'by_copies'
    ];

    protected $casts = [
        'is_for_offer' => 'boolean',
        'is_for_sales' => 'boolean',
        'by_quantity' => 'boolean',
        'by_copies' => 'boolean',
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
            ->withPivot('quantity', 'category_id')
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

    // Method to calculate cost price including categories (for frontend display)
    public function calculateDisplayCostPrice($productArticles = [], $serviceArticles = [])
    {
        $totalCost = 0;

        // Calculate cost from product articles
        foreach ($productArticles as $articleData) {
            if (str_starts_with($articleData['id'], 'cat_')) {
                // This is a category, get the first article's price
                $categoryId = str_replace('cat_', '', $articleData['id']);
                $firstArticle = $this->getFirstArticleFromCategory($categoryId, 'product');
                if ($firstArticle) {
                    $totalCost += ($firstArticle->purchase_price ?? 0) * ($articleData['quantity'] ?? 0);
                }
            } else {
                // This is a regular article
                $article = Article::find($articleData['id']);
                if ($article) {
                    $totalCost += ($article->purchase_price ?? 0) * ($articleData['quantity'] ?? 0);
                }
            }
        }

        // Calculate cost from service articles
        foreach ($serviceArticles as $articleData) {
            if (str_starts_with($articleData['id'], 'cat_')) {
                // This is a category, get the first article's price
                $categoryId = str_replace('cat_', '', $articleData['id']);
                $firstArticle = $this->getFirstArticleFromCategory($categoryId, 'service');
                if ($firstArticle) {
                    $totalCost += ($firstArticle->purchase_price ?? 0) * ($articleData['quantity'] ?? 0);
                }
            } else {
                // This is a regular article
                $article = Article::find($articleData['id']);
                if ($article) {
                    $totalCost += ($article->purchase_price ?? 0) * ($articleData['quantity'] ?? 0);
                }
            }
        }

        return $totalCost;
    }

    // Method to get the first available article from a category (similar to getNextAvailableMaterial)
    public function getFirstArticleFromCategory($categoryId, $type = null, $requiredQuantity = 1)
    {
        $category = ArticleCategory::with('articles')->find($categoryId);
        
        if (!$category || $category->articles->isEmpty()) {
            return null;
        }

        // Filter by type if specified
        $articles = $category->articles;
        if ($type) {
            $articles = $articles->where('type', $type);
        }

        // Get available articles with sufficient stock
        $availableArticles = [];
        
        foreach ($articles as $article) {
            if ($article->hasStock($requiredQuantity)) {
                $availableArticles[] = $article;
            }
        }

        if (empty($availableArticles)) {
            return null; // No articles with sufficient stock
        }

        // Sort by creation date (oldest first for FIFO)
        usort($availableArticles, function($a, $b) {
            return $a->created_at <=> $b->created_at;
        });

        // Return the first (oldest) available article
        return $availableArticles[0];
    }

    // Add this new relationship method
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    // Method to get the pricing method (quantity or copies)
    public function getPricingMethod(): string
    {
        if ($this->by_copies) {
            return 'copies';
        }
        
        // Default to quantity if by_quantity is true or if neither is set
        return 'quantity';
    }

    // Method to get the multiplier value based on pricing method
    public function getPricingMultiplier($quantity, $copies): float
    {
        if ($this->by_copies) {
            return $copies;
        }
        
        // Default to quantity
        return $quantity;
    }

    // Relationship with questions
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'catalog_item_questions');
    }

    /**
     * Calculate actual article requirements for a job
     * Handles all unit types: square meters, pieces, kilograms, linear meters
     * Uses proportional scaling when actual usage exceeds catalog standard
     */
    public function calculateActualArticleRequirements($job)
    {
        $requirements = [];
        
        // Get the pricing multiplier (quantity or copies based on catalog item setting)
        $multiplier = $this->getPricingMultiplier($job->quantity, $job->copies);
        
        // Calculate job dimensions if available
        $jobSquareMeters = 0;
        if ($job->width && $job->height) {
            // Convert mm to meters
            $jobSquareMeters = ($job->width / 1000) * ($job->height / 1000);
        }
        
        foreach ($this->articles as $article) {
            $catalogStandard = $article->pivot->quantity;
            $actualRequired = 0;
            
            if ($article->in_square_meters) {
                // For square meters: use actual job dimensions
                if ($jobSquareMeters > 0) {
                    $actualRequired = $jobSquareMeters * $multiplier;
                } else {
                    // Fallback to catalog standard if no job dimensions
                    $actualRequired = $catalogStandard * $multiplier;
                }
            } else {
                // For all other units (pcs, kg, meters): use catalog standard
                $actualRequired = $catalogStandard * $multiplier;
            }
            
            $requirements[] = [
                'article_id' => $article->id,
                'article' => $article,
                'catalog_standard' => $catalogStandard,
                'multiplier' => $multiplier,
                'actual_required' => $actualRequired,
                'job_square_meters' => $jobSquareMeters,
                'unit_type' => $this->getArticleUnitType($article)
            ];
        }
        
        return $requirements;
    }
    
    /**
     * Get the primary unit type for an article
     */
    private function getArticleUnitType($article)
    {
        if ($article->in_square_meters) return 'square_meters';
        if ($article->in_pieces) return 'pieces';
        if ($article->in_kilograms) return 'kilograms';
        if ($article->in_meters) return 'meters';
        return 'unknown';
    }
    
    /**
     * Calculate total cost price for a job based on actual article requirements
     */
    public function calculateJobCostPrice($job)
    {
        $totalCost = 0;
        $requirements = $this->calculateActualArticleRequirements($job);
        
        foreach ($requirements as $requirement) {
            $article = $requirement['article'];
            $actualRequired = $requirement['actual_required'];
            $articlePrice = $article->purchase_price ?? 0;
            
            $totalCost += $actualRequired * $articlePrice;
        }
        
        return $totalCost;
    }
}
