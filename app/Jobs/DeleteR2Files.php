<?php

namespace App\Jobs;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteR2Files implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct(
        public array $filePaths
    ) {}

    private function s3(): S3Client
    {
        // Get R2 config directly from filesystems config
        $config = config('filesystems.disks.r2');
        
        return new S3Client([
            'version' => 'latest',
            'region' => $config['region'] ?? 'auto',
            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? true,
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
        ]);
    }

    private function bucket(): string
    {
        return config('filesystems.disks.r2.bucket');
    }

    public function handle(): void
    {
        if (empty($this->filePaths)) {
            return;
        }

        Log::info('Starting async deletion of R2 files', [
            'file_count' => count($this->filePaths),
            'files' => $this->filePaths
        ]);

        try {
            $s3 = $this->s3();
            $bucket = $this->bucket();

            // Process files in batches of 1000 (S3 limit for deleteObjects)
            $batches = array_chunk($this->filePaths, 1000);
            $totalDeleted = 0;
            $totalErrors = 0;
            $errors = [];

            foreach ($batches as $batch) {
                $objects = array_map(function($path) {
                    return ['Key' => $path];
                }, $batch);

                try {
                    $result = $s3->deleteObjects([
                        'Bucket' => $bucket,
                        'Delete' => [
                            'Objects' => $objects,
                            'Quiet' => false, // Return info about deleted objects
                        ],
                    ]);

                    $deletedCount = count($result['Deleted'] ?? []);
                    $errorCount = count($result['Errors'] ?? []);
                    
                    $totalDeleted += $deletedCount;
                    $totalErrors += $errorCount;

                    // Collect errors for logging
                    if (!empty($result['Errors'])) {
                        foreach ($result['Errors'] as $error) {
                            $errors[] = [
                                'key' => $error['Key'] ?? 'unknown',
                                'code' => $error['Code'] ?? 'unknown',
                                'message' => $error['Message'] ?? 'unknown error'
                            ];
                        }
                    }

                } catch (S3Exception $e) {
                    Log::error('S3 batch delete failed', [
                        'batch_size' => count($batch),
                        'error' => $e->getMessage(),
                        'files' => $batch
                    ]);
                    $totalErrors += count($batch);
                    $errors[] = [
                        'batch_error' => $e->getMessage(),
                        'affected_files' => count($batch)
                    ];
                }
            }

            Log::info('Completed async deletion of R2 files', [
                'total_files' => count($this->filePaths),
                'successful_deletions' => $totalDeleted,
                'failed_deletions' => $totalErrors,
                'errors' => $errors
            ]);

            if ($totalErrors > 0) {
                Log::warning('Some files failed to delete', [
                    'failed_count' => $totalErrors,
                    'errors' => $errors
                ]);
            }

        } catch (\Exception $e) {
            Log::error('DeleteR2Files job failed with unexpected error', [
                'file_paths' => $this->filePaths,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to trigger job failure handling
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DeleteR2Files job failed', [
            'file_paths' => $this->filePaths,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
