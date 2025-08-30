<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class FileOptimizationService
{
    protected $templateStorageService;
    
    public function __construct(TemplateStorageService $templateStorageService)
    {
        $this->templateStorageService = $templateStorageService;
    }
    
    /**
     * Get optimized file URL with caching
     */
    public function getOptimizedFileUrl(string $path, int $cacheMinutes = 60): string
    {
        $cacheKey = "optimized_file_url_{$path}";
        
        return Cache::remember($cacheKey, $cacheMinutes, function () use ($path) {
            return $this->templateStorageService->getTemplateUrl($path);
        });
    }
    
    /**
     * Get optimized thumbnail URL with size parameter
     */
    public function getOptimizedThumbnailUrl(int $jobId, int $fileIndex, string $size = 'medium'): string
    {
        $cacheKey = "optimized_thumbnail_{$jobId}_{$fileIndex}_{$size}";
        
        return Cache::remember($cacheKey, 60, function () use ($jobId, $fileIndex, $size) {
            $baseUrl = $this->templateStorageService->getTemplateUrl('');
            
            // Generate different thumbnail sizes
            $thumbnailPath = match($size) {
                'small' => "job-thumbnails/small/job_{$jobId}_{$fileIndex}.webp",
                'medium' => "job-thumbnails/job_{$jobId}_{$fileIndex}.webp",
                'large' => "job-thumbnails/large/job_{$jobId}_{$fileIndex}.webp",
                default => "job-thumbnails/job_{$jobId}_{$fileIndex}.webp"
            };
            
            return $baseUrl . '/' . $thumbnailPath;
        });
    }
    
    /**
     * Pre-generate multiple thumbnail sizes for a job
     */
    public function preGenerateThumbnails(int $jobId, int $fileIndex, string $originalFilePath): array
    {
        $sizes = [
            'small' => [300, 300],
            'medium' => [600, 600],
            'large' => [800, 800]
        ];
        
        $generated = [];
        
        foreach ($sizes as $size => $dimensions) {
            try {
                $thumbnailPath = $this->generateOptimizedThumbnail(
                    $originalFilePath, 
                    $jobId, 
                    $fileIndex, 
                    $dimensions[0], 
                    $dimensions[1],
                    $size
                );
                
                if ($thumbnailPath) {
                    $generated[$size] = $thumbnailPath;
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to generate {$size} thumbnail", [
                    'job_id' => $jobId,
                    'file_index' => $fileIndex,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $generated;
    }
    
    /**
     * Generate optimized thumbnail with specific dimensions
     */
    protected function generateOptimizedThumbnail(
        string $originalFilePath, 
        int $jobId, 
        int $fileIndex, 
        int $width, 
        int $height,
        string $size
    ): ?string {
        try {
            $imagick = new \Imagick();
            $imagick->readImage($originalFilePath . '[0]');
            
            // Convert to WebP for better compression
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality(75);
            $imagick->stripImage();
            
            // Resize to specified dimensions
            $imagick->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1, true);
            
            // Create thumbnail blob
            $thumbnailBlob = $imagick->getImageBlob();
            $imagick->clear();
            
            // Store in R2 with size-specific path
            $thumbnailPath = "job-thumbnails/{$size}/job_{$jobId}_{$fileIndex}.webp";
            $this->templateStorageService->getDisk()->put($thumbnailPath, $thumbnailBlob);
            
            return $thumbnailPath;
            
        } catch (\Exception $e) {
            \Log::error("Failed to generate optimized thumbnail", [
                'job_id' => $jobId,
                'file_index' => $fileIndex,
                'size' => $size,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Get file size and type information for optimization
     */
    public function getFileInfo(string $path): array
    {
        $disk = $this->templateStorageService->getDisk();
        
        if (!$disk->exists($path)) {
            return ['error' => 'File not found'];
        }
        
        $metadata = $disk->getVisibility($path);
        $size = $disk->size($path);
        $mimeType = $disk->mimeType($path);
        
        return [
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'mime_type' => $mimeType,
            'visibility' => $metadata,
            'path' => $path
        ];
    }
    
    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

