<?php

namespace App\Jobs;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessPdfDimensions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes for large PDFs

    public function __construct(
        public int $jobId,
        public string $r2Key,
        public ?string $clientFilename = null
    ) {}

    public function handle(): void
    {
        $job = Job::find($this->jobId);
        if (!$job) return;

        try {
            $disk = Storage::disk('r2');
            if (!$disk->exists($this->r2Key)) {
                \Log::warning('ProcessPdfDimensions: file missing in R2', ['key' => $this->r2Key]);
                return;
            }
            $stream = $disk->readStream($this->r2Key);
            if (!$stream) {
                \Log::warning('ProcessPdfDimensions: cannot read stream', ['key' => $this->r2Key]);
                return;
            }

            // Save to a temp file
            $tempPath = storage_path('app/temp/pdfdim_' . $job->id . '_' . time() . '.pdf');
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            $out = fopen($tempPath, 'wb');
            stream_copy_to_stream($stream, $out);
            fclose($out);
            if (is_resource($stream)) fclose($stream);

            // Use Node.js pdf-lib for accurate page count and real dimensions
            \Log::info('Starting pdf-lib dimension calculation', [
                'job_id' => $job->id,
                'pdf_path' => $tempPath,
                'method' => 'pdf-lib'
            ]);
            
            $result = $this->getPdfDimensionsWithNode($tempPath);
            
            if (!$result) {
                throw new \Exception('Failed to extract PDF dimensions');
            }
            
            $pageCount = $result['pageCount'];
            $pageDimensions = $result['pages'];
            $totalArea = array_sum(array_column($pageDimensions, 'area_m2'));

            $existing = is_array($job->dimensions_breakdown) ? $job->dimensions_breakdown : [];
            $existing[] = [
                'filename' => $this->clientFilename ?? basename($this->r2Key),
                'page_count' => $pageCount,
                'total_area_m2' => round($totalArea, 6),
                'page_dimensions' => $pageDimensions,
                'index' => count($existing),
            ];

            $job->dimensions_breakdown = $existing;
            $job->total_area_m2 = round(($job->total_area_m2 ?? 0) + $totalArea, 6);
            if (!empty($existing[0]['page_dimensions'])) {
                $first = $existing[0]['page_dimensions'][0];
                $job->width = $first['width_mm'];
                $job->height = $first['height_mm'];
            }
            $job->save();

            // Cleanup
            if (file_exists($tempPath)) @unlink($tempPath);

            \Log::info('ProcessPdfDimensions complete', [
                'job_id' => $job->id,
                'key' => $this->r2Key,
                'pages' => $pageCount,
                'area' => $job->total_area_m2,
                'method' => 'pdf-lib',
                'memory_efficient' => true,
                'pure_javascript' => true
            ]);
        } catch (\Throwable $e) {
            \Log::error('ProcessPdfDimensions failed', [
                'job_id' => $this->jobId,
                'key' => $this->r2Key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get PDF dimensions using Node.js pdfinfojs (pure JavaScript, no external dependencies)
     */
    private function getPdfDimensionsWithNode(string $pdfPath): ?array
    {
        try {
            // Get the path to our Node.js script
            $scriptPath = base_path('scripts/pdf-dimensions-accurate.cjs');
            
            if (!file_exists($scriptPath)) {
                \Log::error('PDF dimensions script not found', [
                    'script_path' => $scriptPath
                ]);
                return null;
            }
            
            // Execute the Node.js script
            $command = "node \"$scriptPath\" \"$pdfPath\" 2>&1";
            $output = shell_exec($command);
            
            if (!$output) {
                \Log::error('Node.js script returned no output', [
                    'pdf_path' => $pdfPath,
                    'command' => $command
                ]);
                return null;
            }
            
            // Parse JSON output
            $result = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('Failed to parse Node.js script output', [
                    'pdf_path' => $pdfPath,
                    'output' => $output,
                    'json_error' => json_last_error_msg()
                ]);
                return null;
            }
            
            \Log::info('pdf-lib extraction successful', [
                'pdf_path' => $pdfPath,
                'page_count' => $result['pageCount'] ?? 0,
                'method' => 'pdf-lib'
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error('Error running pdf-lib script', [
                'pdf_path' => $pdfPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}


