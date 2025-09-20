<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockRealization extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'invoice_title',
        'client_id',
        'start_date',
        'end_date',
        'comment',
        'is_realized',
        'realized_at',
        'realized_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_realized' => 'boolean',
        'realized_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function realizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realized_by');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(StockRealizationJob::class);
    }

    /**
     * Create stock realization from completed invoice
     */
    public static function createFromInvoice(Invoice $invoice): self
    {
        $invoice->loadMissing(['jobs', 'client']);
        
        $stockRealization = self::create([
            'invoice_id' => $invoice->id,
            'invoice_title' => $invoice->invoice_title,
            'client_id' => $invoice->client_id,
            'start_date' => $invoice->start_date,
            'end_date' => $invoice->end_date,
            'comment' => $invoice->comment,
        ]);

        // Create stock realization jobs
        foreach ($invoice->jobs as $job) {
            $stockRealizationJob = $stockRealization->jobs()->create([
                'job_id' => $job->id,
                'name' => $job->name,
                'quantity' => $job->quantity,
                'copies' => $job->copies,
                'total_area_m2' => $job->total_area_m2,
                'width' => $job->width,
                'height' => $job->height,
                'dimensions_breakdown' => $job->dimensions_breakdown,
                'small_material_id' => $job->small_material_id,
                'large_material_id' => $job->large_material_id,
                'catalog_item_id' => $job->catalog_item_id,
            ]);

            // Create stock realization articles
            self::createArticlesForJob($stockRealizationJob, $job);
        }

        return $stockRealization;
    }

    /**
     * Create articles for a stock realization job
     */
    private static function createArticlesForJob(StockRealizationJob $stockRealizationJob, Job $job): void
    {
        $articlesToConsume = [];

        // Get articles from catalog item if available
        if ($job->catalogItem && $job->catalogItem->articles()->exists()) {
            $materialRequirements = $job->catalogItem->calculateMaterialRequirements($job);

            foreach ($materialRequirements as $requirement) {
                $article = $requirement['article'];
                $neededQuantity = $requirement['actual_required'];
                $unitType = $requirement['unit_type'];

                // Resolve category to an actual article at consumption time
                $actualArticle = $article;
                if ($article->pivot->category_id) {
                    $actualArticle = $job->catalogItem->getFirstArticleFromCategory(
                        $article->pivot->category_id,
                        null,
                        $neededQuantity
                    );
                    if (!$actualArticle) {
                        continue;
                    }
                }

                $articlesToConsume[] = [
                    'article' => $actualArticle,
                    'quantity' => $neededQuantity,
                    'unit_type' => $unitType,
                    'source' => 'catalog_item_recomputed'
                ];
            }
        } elseif ($job->articles && $job->articles->count() > 0) {
            // Manual jobs: use direct job article assignments
            foreach ($job->articles as $jobArticle) {
                $articlesToConsume[] = [
                    'article' => $jobArticle,
                    'quantity' => $jobArticle->pivot->quantity,
                    'unit_type' => 'pieces',
                    'source' => 'job_direct'
                ];
            }
        }

        // Create stock realization articles
        foreach ($articlesToConsume as $articleData) {
            $stockRealizationJob->articles()->create([
                'article_id' => $articleData['article']->id,
                'quantity' => $articleData['quantity'],
                'unit_type' => $articleData['unit_type'],
                'source' => $articleData['source'],
            ]);
        }
    }

    /**
     * Realize the stock deduction
     */
    public function realize(): bool
    {
        if ($this->is_realized) {
            return false; // Already realized
        }

        try {
            \DB::beginTransaction();

            $this->loadMissing(['jobs.articles.article']);

            foreach ($this->jobs as $job) {
                // Process articles first (new system)
                foreach ($job->articles as $articleData) {
                    $article = $articleData->article;
                    $quantity = $articleData->quantity;
                    
                    $this->consumeArticleStock($article, $quantity);
                }

                // Process legacy materials if no articles
                if ($job->articles->isEmpty()) {
                    // Legacy large material
                    if ($job->large_material_id) {
                        $largeMaterial = LargeFormatMaterial::with('article')->find($job->large_material_id);
                        if ($largeMaterial) {
                            $units = ($job->catalog_item_id && optional($job->catalogItem)->by_copies) ? $job->copies : $job->quantity;
                            
                            if ($largeMaterial->article && $largeMaterial->article->in_square_meters === 1) {
                                $largeMaterial->quantity = max(0, $largeMaterial->quantity - ($units * ($job->total_area_m2 ?? 0)));
                            } else {
                                $largeMaterial->quantity = max(0, $largeMaterial->quantity - $units);
                            }
                            $largeMaterial->save();
                        }
                    }

                    // Legacy small material
                    if ($job->small_material_id) {
                        $smallMaterial = SmallMaterial::with('article')->find($job->small_material_id);
                        if ($smallMaterial) {
                            $units = ($job->catalog_item_id && optional($job->catalogItem)->by_copies) ? $job->copies : $job->quantity;
                            
                            if ($smallMaterial->article && $smallMaterial->article->in_square_meters === 1) {
                                $smallMaterial->quantity = max(0, $smallMaterial->quantity - ($units * ($job->total_area_m2 ?? 0)));
                            } else {
                                $smallMaterial->quantity = max(0, $smallMaterial->quantity - $units);
                            }
                            $smallMaterial->save();
                        }
                    }
                }
            }

            // Mark as realized
            $this->update([
                'is_realized' => true,
                'realized_at' => now(),
                'realized_by' => auth()->id(),
            ]);

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('Stock realization failed', [
                'stock_realization_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Consume article stock (same logic as in InvoiceController)
     */
    private function consumeArticleStock($article, $quantity): void
    {
        // For material-type articles, reduce the corresponding material quantity
        if ($article->format_type == 1 && $article->smallMaterial) {
            $material = $article->smallMaterial;
            $material->quantity = max(0, $material->quantity - $quantity);
            $material->save();
        } elseif ($article->format_type == 2 && $article->largeFormatMaterial) {
            $material = $article->largeFormatMaterial;
            $material->quantity = max(0, $material->quantity - $quantity);
            $material->save();
        } elseif ($article->format_type == 3) {
            $material = \App\Models\OtherMaterial::where('article_id', $article->id)->first();
            if ($material) {
                $material->quantity = max(0, $material->quantity - $quantity);
                $material->save();
            }
        } else {
            // For regular product/service articles, create a consumption record
            $consumptionPriemnica = \App\Models\Priemnica::create([
                'warehouse' => 1, // Default warehouse
                'client_id' => null,
                'comment' => "Stock realization consumption - Article ID: {$article->id}"
            ]);
            
            // Attach the article with negative quantity to track consumption
            $consumptionPriemnica->articles()->attach($article->id, ['quantity' => -$quantity]);
        }
    }
}
