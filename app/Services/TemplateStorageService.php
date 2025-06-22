<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateStorageService
{
    public $disk;
    
    public function __construct()
    {
        $this->disk = Storage::disk('r2');
    }
    
    /**
     * Get the storage disk instance
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function getDisk()
    {
        return $this->disk;
    }
    
    /**
     * Store a template file
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return string The stored file path
     */
    public function storeTemplate(UploadedFile $file, string $directory = 'templates'): string
    {
        // Generate a unique filename with timestamp
        $timestamp = now()->timestamp;
        
        // Get original filename with fallback handling
        $originalName = $file->getClientOriginalName();
        
        // Handle cases where getClientOriginalName() returns a temp path or invalid name
        if (!$originalName || 
            str_contains($originalName, 'php') || 
            str_contains($originalName, 'tmp') || 
            str_contains($originalName, '\\') || 
            str_contains($originalName, '/')) {
            
            // Use a fallback filename
            $originalName = 'template_' . $timestamp . '.pdf';
        }
        
        // Extract extension safely
        $extension = $file->getClientOriginalExtension() ?: 'pdf';
        
        // Clean the filename and create a safe filename
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = Str::slug($baseName) ?: 'template';
        $filename = $timestamp . '_' . $safeName . '.' . $extension;
        
        // Store the file in R2
        $path = $directory . '/' . $filename;
        $this->disk->putFileAs($directory, $file, $filename, 'public');
        
        return $path;
    }
    
    /**
     * Delete a template file
     *
     * @param string $path
     * @return bool
     */
    public function deleteTemplate(string $path): bool
    {
        try {
            if ($this->disk->exists($path)) {
                $result = $this->disk->delete($path);
                return $result;
            }
            
            return true; // File doesn't exist, consider it deleted
            
        } catch (\Exception $e) {
            throw $e; // Re-throw to allow calling code to handle
        }
    }
    
    /**
     * Get a temporary signed URL for a template (valid for 1 hour)
     *
     * @param string $path
     * @return string
     */
    public function getSignedTemplateUrl(string $path): string
    {
        try {
            // Generate a signed URL valid for 1 hour
            return $this->disk->temporaryUrl($path, now()->addHour());
        } catch (\Exception $e) {
            throw $e; // Re-throw to allow calling code to handle
        }
    }
    
    /**
     * Get the public URL for a template
     *
     * @param string $path
     * @return string
     */
    public function getTemplateUrl(string $path): string
    {
        // If R2_URL is not set or is the placeholder, use the default storage URL
        $customUrl = config('filesystems.disks.r2.url');
        
        if (!$customUrl || str_contains($customUrl, 'your-custom-domain.com')) {
            // Use the storage disk's default URL method
            return $this->disk->url($path);
        }
        
        return $customUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Check if a template file exists
     *
     * @param string $path
     * @return bool
     */
    public function templateExists(string $path): bool
    {
        return $this->disk->exists($path);
    }
    
    /**
     * Get the original filename from a stored path
     *
     * @param string $path
     * @return string
     */
    public function getOriginalFilename(string $path): string
    {
        $filename = basename($path);
        // Remove timestamp prefix (numbers followed by underscore)
        return preg_replace('/^\d+_/', '', $filename);
    }
    
    /**
     * Download a template file
     *
     * @param string $path
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadTemplate(string $path)
    {
        if (!$this->templateExists($path)) {
            abort(404, 'Template file not found');
        }
        
        $originalName = $this->getOriginalFilename($path);
        
        return $this->disk->download($path, $originalName);
    }
    
    /**
     * List all files in the templates directory
     *
     * @return array
     */
    public function listTemplateFiles(): array
    {
        try {
            return $this->disk->files('templates');
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Clean up orphaned template files (files not referenced in database)
     * Use with caution - this will permanently delete files!
     *
     * @return array Results of cleanup operation
     */
    public function cleanupOrphanedTemplates(): array
    {
        $results = [
            'total_files' => 0,
            'referenced_files' => 0,
            'orphaned_files' => 0,
            'deleted_files' => 0,
            'errors' => []
        ];
        
        try {
            // Get all template files from R2
            $allFiles = $this->listTemplateFiles();
            $results['total_files'] = count($allFiles);
            
            // Get all template file paths from database
            $referencedFiles = \App\Models\CatalogItem::whereNotNull('template_file')
                ->pluck('template_file')
                ->toArray();
            $results['referenced_files'] = count($referencedFiles);
            
            // Find orphaned files
            $orphanedFiles = array_diff($allFiles, $referencedFiles);
            $results['orphaned_files'] = count($orphanedFiles);
            
            // Delete orphaned files
            foreach ($orphanedFiles as $orphanedFile) {
                try {
                    if ($this->deleteTemplate($orphanedFile)) {
                        $results['deleted_files']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Failed to delete {$orphanedFile}: " . $e->getMessage();
                    }
            }
            
        } catch (\Exception $e) {
            $results['errors'][] = "Cleanup operation failed: " . $e->getMessage();
        }
        
        return $results;
    }
} 