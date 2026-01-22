<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\JobAction;
use App\Enums\MachineCut;
use App\Enums\MachinePrint;
use App\Enums\Material;
use App\Enums\MaterialSmall;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Dorabotka;

class Job extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($job) {
            // Clean up original files from R2 storage when job is being deleted
            if ($job->hasOriginalFiles()) {
                $templateStorageService = app()->make(\App\Services\TemplateStorageService::class);
                $originalFiles = $job->getOriginalFiles();
                
                foreach ($originalFiles as $filePath) {
                    if ($filePath && str_starts_with($filePath, 'job-originals/')) {
                        try {
                            $templateStorageService->deleteTemplate($filePath);
                            \Log::info('Model cleanup: Successfully deleted original file from R2', [
                                'job_id' => $job->id,
                                'file_path' => $filePath
                            ]);
                        } catch (\Exception $e) {
                            \Log::warning('Model cleanup: Failed to delete original file from R2 storage: ' . $e->getMessage(), [
                                'job_id' => $job->id,
                                'file_path' => $filePath,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }

            // Clean up thumbnails from R2 storage when job is being deleted
            try {
                $templateStorageService = app()->make(\App\Services\TemplateStorageService::class);
                $thumbnailFiles = $templateStorageService->getDisk()->files('job-thumbnails');
                $removedCount = 0;
                
                foreach ($thumbnailFiles as $thumbnail) {
                    $thumbBasename = basename($thumbnail);
                    // Check if this thumbnail belongs to this job
                    if (strpos($thumbBasename, 'job_' . $job->id . '_') === 0) {
                        try {
                            $templateStorageService->getDisk()->delete($thumbnail);
                            $removedCount++;
                            \Log::info('Model cleanup: Deleted job thumbnail from R2', [
                                'job_id' => $job->id,
                                'thumbnail' => basename($thumbnail)
                            ]);
                        } catch (\Exception $e) {
                            \Log::warning('Model cleanup: Failed to delete thumbnail: ' . $e->getMessage(), [
                                'thumbnail' => $thumbnail
                            ]);
                        }
                    }
                }
                
                if ($removedCount > 0) {
                    \Log::info('Model cleanup: Thumbnail cleanup completed', [
                        'job_id' => $job->id,
                        'removed_thumbnails' => $removedCount
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Model cleanup: Failed to cleanup thumbnails: ' . $e->getMessage(), [
                    'job_id' => $job->id,
                    'error' => $e->getMessage()
                ]);
            }
        });
    }

    protected $fillable = [
        'file',
        'originalFile',
        'width',
        'height',
        'total_area_m2',
        'dimensions_breakdown',
        'quantity',
        'copies',
        'machinePrint',
        'machineCut',
        'large_material_id',
        'small_material_id',
        'shippingInfo',
        'status',
        'name',
        'catalog_item_id',
        'client_id',
        'price',
        'salePrice',
        'invoice_id',
        'faktura_id',
        'unit'
    ];

    protected $with = ['actions', 'invoice', 'articles'];

    protected $appends = ['effective_catalog_item_id', 'effective_client_id', 'computed_total_area_m2'];

    protected $attributes = [
        'estimatedTime' => 0,
        'shippingInfo' => '',
        'status' => 'Not started yet',
    ];

    protected array $enumCasts = [
        'status' => InvoiceStatus::class,
    ];

    protected $casts = [
        'question_answers' => 'array',
        'originalFile' => 'array',
        'cuttingFiles' => 'array',
        'cuttingFileDimensions' => 'array',
        'dimensions_breakdown' => 'array',
        'total_area_m2' => 'decimal:6',
    ];

    /**
     * Get the computed total area in square meters
     */
    public function getComputedTotalAreaM2Attribute()
    {
        // Try to calculate from dimensions_breakdown first
        if ($this->dimensions_breakdown && is_array($this->dimensions_breakdown)) {
            $totalArea = 0;
            foreach ($this->dimensions_breakdown as $file) {
                if (isset($file['total_area_m2']) && is_numeric($file['total_area_m2'])) {
                    $totalArea += (float) $file['total_area_m2'];
                }
            }
            if ($totalArea > 0) {
                return round($totalArea, 6);
            }
        }

        // Fallback to width/height calculation if available
        if ($this->width && $this->height && is_numeric($this->width) && is_numeric($this->height)) {
            $areaM2 = ($this->width * $this->height) / 1000000; // Convert mm² to m²
            return round($areaM2, 6);
        }

        // Return 0 if no calculation is possible
        return 0.0;
    }

    public function getEffectiveCatalogItemIdAttribute()
    {
        return $this->catalog_item_id ?? $this->invoice?->catalog_item_id;
    }

    public function getEffectiveClientIdAttribute()
    {
        return $this->client_id ?? $this->invoice?->client_id;
    }

    public function actions(): BelongsToMany
    {
        return $this->belongsToMany(JobAction::class, 'job_job_action', 'job_id', 'job_action_id')
            ->withPivot(['status', 'quantity']);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function faktura(): BelongsTo
    {
        return $this->belongsTo(Faktura::class);
    }

    public function small_material()
    {
        return $this->belongsTo(SmallMaterial::class, 'small_material_id');
    }

    public function startedByUser()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function large_material()
    {
        return $this->belongsTo(LargeFormatMaterial::class, 'large_material_id');
    }

    // New relationship for multiple articles
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'job_articles')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function notes()
    {
        return $this->hasMany(JobNote::class);
    }

    public function getTotalPriceAttribute(): float|int
    {
        $smallMaterial = SmallMaterial::with('article')->find($this->small_material_id);
        $largeMaterial = LargeFormatMaterial::with('article')->find($this->large_material_id);
        $price = 0;
        $job = Job::with('actions')->find($this->id)->toArray();
        foreach ($job['actions'] as $action) {
            if ($action['quantity']) {
                if (isset($smallMaterial)) {
                    $price = $price + ($action['quantity']*$smallMaterial->article->price_1);
                }
                if (isset($largeMaterial)) {
                    $price = $price + ($action['quantity']*$largeMaterial->article->price_1);
                }
            }
        }
        return $price;
    }

    public function scopeWithActionStatusCounts($query)
    {
        return $query->withCount(['actions as action_status_count' => function ($query) {
            $query->select(DB::raw("concat(job_action_id, '-', status) as actionstatus"))
                ->groupBy('status', 'job_action_id');
        }]);
    }

    /**
     * Add a new original file to the job
     */
    public function addOriginalFile(string $filePath): void
    {
        $currentFiles = $this->originalFile ?? [];
        if (!in_array($filePath, $currentFiles)) {
            $currentFiles[] = $filePath;
            $this->originalFile = $currentFiles;
        }
    }

    /**
     * Get all original files
     */
    public function getOriginalFiles(): array
    {
        return $this->originalFile ?? [];
    }

    /**
     * Get the primary (first) original file
     */
    public function getPrimaryOriginalFile(): ?string
    {
        $files = $this->getOriginalFiles();
        return !empty($files) ? $files[0] : null;
    }

    /**
     * Remove an original file from the job
     */
    public function removeOriginalFile(string $filePath): bool
    {
        $currentFiles = $this->originalFile ?? [];
        $key = array_search($filePath, $currentFiles);
        
        if ($key !== false) {
            unset($currentFiles[$key]);
            $this->originalFile = array_values($currentFiles); // Reindex array
            return true;
        }
        
        return false;
    }

    /**
     * Check if the job has any original files
     */
    public function hasOriginalFiles(): bool
    {
        $files = $this->getOriginalFiles();
        return !empty($files);
    }

    /**
     * Add a new cutting file to the job
     */
    public function addCuttingFile(string $filePath): void
    {
        $currentFiles = $this->cuttingFiles ?? [];
        if (!in_array($filePath, $currentFiles)) {
            $currentFiles[] = $filePath;
            $this->cuttingFiles = $currentFiles;
        }
    }

    /**
     * Get all cutting files
     */
    public function getCuttingFiles(): array
    {
        return $this->cuttingFiles ?? [];
    }

    /**
     * Remove a cutting file from the job
     */
    public function removeCuttingFile(string $filePath): bool
    {
        $currentFiles = $this->cuttingFiles ?? [];
        $key = array_search($filePath, $currentFiles);
        if ($key !== false) {
            unset($currentFiles[$key]);
            $this->cuttingFiles = array_values($currentFiles); // Reindex array
            return true;
        }
        return false;
    }

    /**
     * Check if the job has any cutting files
     */
    public function hasCuttingFiles(): bool
    {
        $files = $this->getCuttingFiles();
        return !empty($files);
    }
}
