<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPdfDimensions;
use App\Jobs\GeneratePdfThumbnails;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class MultipartUploadController extends Controller
{
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

    public function start(Request $request)
    {
        $data = $request->validate([
            'filename' => 'required|string',
            'contentType' => 'required|string',
            'size' => 'required|integer|min:1',
            'prefix' => 'sometimes|string', // default job-originals
        ]);

        $prefix = $data['prefix'] ?? 'job-originals';
        $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_\-.]+/','_', $data['filename']);
        $key = trim($prefix, '/') . '/' . $safeName;

        try {
            $s3 = $this->s3();
            $result = $s3->createMultipartUpload([
                'Bucket' => $this->bucket(),
                'Key' => $key,
                'ContentType' => $data['contentType'],
            ]);

            return response()->json([
                'key' => $key,
                'uploadId' => $result['UploadId'] ?? null,
            ]);
        } catch (S3Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function signPart(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string',
            'uploadId' => 'required|string',
            'partNumber' => 'required|integer|min:1',
            'expiresIn' => 'sometimes|integer|min:60|max:3600',
        ]);

        try {
            $s3 = $this->s3();
            $cmd = $s3->getCommand('UploadPart', [
                'Bucket' => $this->bucket(),
                'Key' => $data['key'],
                'UploadId' => $data['uploadId'],
                'PartNumber' => $data['partNumber'],
            ]);
            $requestSigned = $s3->createPresignedRequest($cmd, '+' . ($data['expiresIn'] ?? 900) . ' seconds');
            $url = (string) $requestSigned->getUri();

            return response()->json(['url' => $url]);
        } catch (S3Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function complete(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string',
            'uploadId' => 'required|string',
            'parts' => 'required|array|min:1', // [{ETag, PartNumber}]
            'parts.*.ETag' => 'required|string',
            'parts.*.PartNumber' => 'required|integer|min:1',
            'job_id' => 'required|exists:jobs,id',
            'original_filename' => 'sometimes|string',
        ]);

        \Log::info('Starting multipart completion', [
            'key' => $data['key'],
            'uploadId' => $data['uploadId'],
            'parts_count' => count($data['parts']),
            'job_id' => $data['job_id']
        ]);

        // Ensure parts are sorted by PartNumber
        usort($data['parts'], function($a, $b) { return ($a['PartNumber'] <=> $b['PartNumber']); });

        try {
            $s3 = $this->s3();
            
            \Log::info('Calling S3 completeMultipartUpload', [
                'key' => $data['key'],
                'uploadId' => $data['uploadId'],
                'parts' => $data['parts']
            ]);

            $result = $s3->completeMultipartUpload([
                'Bucket' => $this->bucket(),
                'Key' => $data['key'],
                'UploadId' => $data['uploadId'],
                'MultipartUpload' => [ 'Parts' => $data['parts'] ],
            ]);

            \Log::info('S3 multipart completion successful', [
                'key' => $data['key'],
                'location' => $result['Location'] ?? null
            ]);

            // Attach to job immediately (determine file type by key prefix)
            $job = Job::findOrFail($data['job_id']);
            $isCuttingFile = str_starts_with($data['key'], 'job-cutting');
            
            if ($isCuttingFile) {
                $job->addCuttingFile($data['key']);
            } else {
                $job->addOriginalFile($data['key']);
            }
            $job->save();

            \Log::info('Job updated with new file', [
                'job_id' => $job->id,
                'file_key' => $data['key'],
                'is_cutting_file' => $isCuttingFile,
                'original_files_count' => count($job->getOriginalFiles()),
                'cutting_files_count' => count($job->getCuttingFiles())
            ]);

            // Only process dimensions for original files, not cutting files
            $dimensionsResponse = null;
            if (!$isCuttingFile) {
                try {
                    $dimensionsResponse = $this->syncExtractPdfDimensions(
                        r2Key: $data['key'],
                        clientFilename: $data['original_filename'] ?? basename($data['key']),
                        job: $job
                    );
                } catch (\Throwable $e) {
                    \Log::warning('Synchronous PDF dimension extraction failed, will fallback to async job', [
                        'job_id' => $job->id,
                        'key' => $data['key'],
                        'error' => $e->getMessage(),
                    ]);
                }

                // Fallback to async job only if sync extraction failed
                if ($dimensionsResponse === null) {
                    ProcessPdfDimensions::dispatch(
                        jobId: $job->id,
                        r2Key: $data['key'],
                        clientFilename: $data['original_filename'] ?? basename($data['key'])
                    )->delay(now()->addSeconds(5));
                } else {
                    \Log::info('Sync PDF dimensions extraction succeeded, skipping fallback ProcessPdfDimensions job', [
                        'job_id' => $job->id,
                        'key' => $data['key'],
                    ]);
                }
            }

            \Log::info('Multipart completion fully successful', [
                'key' => $data['key'],
                'job_id' => $job->id
            ]);

            // Generate thumbnails AFTER successful upload completion
            try {
                if ($isCuttingFile) {
                    // For cutting files, determine the file index
                    $cuttingFiles = $job->getCuttingFiles();
                    $fileIndex = array_search($data['key'], $cuttingFiles);
                    
                    \Log::info('Starting synchronous cutting file thumbnail generation', [
                        'job_id' => $job->id,
                        'r2_key' => $data['key'],
                        'file_index' => $fileIndex
                    ]);
                    
                    // Small delay to ensure R2 consistency after completion
                    sleep(1);
                    
                    // For cutting files use a higher default DPI to avoid blur on small artboards
                    $cuttingDpi = 200;
                    GeneratePdfThumbnails::dispatchSync(
                        jobId: $job->id,
                        r2Key: $data['key'],
                        tempLocalPath: null,
                        dpi: $cuttingDpi,
                        fileIndex: $fileIndex,
                        isCuttingFile: true
                    );
                } else {
                    // Original file thumbnail generation
                    if (!empty($dimensionsResponse['__temp_pdf_path'])) {
                        // Compute an appropriate DPI from physical page size to reach a minimum pixel size
                        $computedDpi = $this->computeDpiForPages($dimensionsResponse['dimensions_breakdown'] ?? [], 1200, 150, 400);
                        \Log::info('Starting synchronous thumbnail generation with temp file (after completion)', [
                            'job_id' => $job->id,
                            'r2_key' => $data['key'],
                            'temp_path' => $dimensionsResponse['__temp_pdf_path']
                        ]);
                        
                        GeneratePdfThumbnails::dispatchSync(
                            jobId: $job->id,
                            r2Key: $data['key'],
                            tempLocalPath: $dimensionsResponse['__temp_pdf_path'],
                            dpi: $computedDpi
                        );
                    } else {
                        \Log::info('Starting synchronous thumbnail generation without temp file (after completion)', [
                            'job_id' => $job->id,
                            'r2_key' => $data['key']
                        ]);
                        
                        // Small delay to ensure R2 consistency after completion
                        sleep(1);
                        // Fallback DPI if we couldn't read dimensions
                        $fallbackDpi = 200;
                        GeneratePdfThumbnails::dispatchSync(
                            jobId: $job->id,
                            r2Key: $data['key'],
                            tempLocalPath: null,
                            dpi: $fallbackDpi
                        );
                    }
                }
            } catch (\Throwable $thumbError) {
                \Log::error('Thumbnail generation failed after successful upload', [
                    'job_id' => $job->id,
                    'r2_key' => $data['key'],
                    'is_cutting_file' => $isCuttingFile,
                    'error' => $thumbError->getMessage()
                ]);
            }

            // Return different response format based on file type
            if ($isCuttingFile) {
                return response()->json([
                    'message' => 'Upload completed',
                    'location' => $result['Location'] ?? null,
                    'key' => $data['key'],
                    'cuttingFiles' => $job->getCuttingFiles(),
                    'uploadedCount' => 1,
                    'thumbnails' => [] // Thumbnails will be loaded separately
                ]);
            } else {
                return response()->json([
                    'message' => 'Upload completed',
                    'location' => $result['Location'] ?? null,
                    'key' => $data['key'],
                    // Include updated job data so UI updates immediately
                    'originalFiles' => $job->getOriginalFiles(),
                    'dimensions_breakdown' => $dimensionsResponse['dimensions_breakdown'] ?? ($job->dimensions_breakdown ?? []),
                    'total_area_m2' => $dimensionsResponse['total_area_m2'] ?? ($job->total_area_m2 ?? 0),
                ]);
            }
        } catch (S3Exception $e) {
            \Log::error('S3 multipart completion failed', [
                'key' => $data['key'],
                'uploadId' => $data['uploadId'],
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            \Log::error('Multipart completion failed with unexpected error', [
                'key' => $data['key'],
                'uploadId' => $data['uploadId'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal server error during completion'], 500);
        }
    }

    /**
     * Compute a reasonable DPI for thumbnails based on page dimensions in millimeters.
     * Expects an array shaped like dimensions_breakdown (with page_dimensions entries containing width_mm/height_mm).
     */
    private function computeDpiForPages(array $dimensionsBreakdown, int $targetLongEdgePx = 1200, int $minDpi = 150, int $maxDpi = 400): int
    {
        // Try to read the first page physical size
        try {
            if (!empty($dimensionsBreakdown)) {
                $first = $dimensionsBreakdown[count($dimensionsBreakdown) - 1]; // latest added file first
                if (isset($first['page_dimensions'][0]['width_mm']) && isset($first['page_dimensions'][0]['height_mm'])) {
                    $wMm = (float)($first['page_dimensions'][0]['width_mm']);
                    $hMm = (float)($first['page_dimensions'][0]['height_mm']);
                    $longMm = max($wMm, $hMm);
                    $longIn = $longMm / 25.4;
                    if ($longIn > 0) {
                        // Safety caps: for extremely large pages, reduce DPI target and bounds
                        if ($longMm >= 2000 /* 2 meters */) {
                            $targetLongEdgePx = 2400; // cap raster size to keep memory bounded
                            $minDpi = 30;              // allow low DPI for huge physical pages
                            $maxDpi = 200;             // avoid excessive DPI on large pages
                        }
                        $dpi = (int)ceil($targetLongEdgePx / $longIn);
                        return max($minDpi, min($maxDpi, $dpi));
                    }
                }
            }
        } catch (\Throwable $e) {
            // fall through to default
        }
        return $minDpi; // conservative default
    }

    /**
     * Synchronously extract PDF dimensions using the Node script and update the Job.
     * Returns array with 'dimensions_breakdown' and 'total_area_m2' on success, null on failure.
     */
    private function syncExtractPdfDimensions(string $r2Key, string $clientFilename, Job $job): ?array
    {
        $disk = Storage::disk('r2');
        // R2 may be briefly inconsistent immediately after multipart completion; retry a few times
        $attempts = 0;
        $maxAttempts = 10; // ~5 seconds total
        $stream = null;
        while ($attempts < $maxAttempts) {
            if ($disk->exists($r2Key)) {
                $stream = $disk->readStream($r2Key);
                if ($stream) break;
            }
            $attempts++;
            usleep(500000); // 500ms
        }
        if (!$stream) {
            \Log::warning('syncExtractPdfDimensions: cannot read stream after retries', [
                'key' => $r2Key,
                'attempts' => $attempts,
            ]);
            return null;
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

        try {
            // Run Node script to extract dimensions
            $result = $this->runNodePdfDimensions($tempPath);
            if (!$result || empty($result['pageCount']) || empty($result['pages'])) {
                return null;
            }

            $pageCount = (int) $result['pageCount'];
            $pageDimensions = $result['pages'];
            $totalArea = array_sum(array_map(static function ($p) {
                return (float) ($p['area_m2'] ?? 0);
            }, $pageDimensions));

            $existing = is_array($job->dimensions_breakdown) ? $job->dimensions_breakdown : [];
            $existing[] = [
                'filename' => $clientFilename,
                'page_count' => $pageCount,
                'total_area_m2' => round($totalArea, 6),
                'page_dimensions' => $pageDimensions,
                'index' => count($existing),
            ];

            $job->dimensions_breakdown = $existing;
            $job->total_area_m2 = round(($job->total_area_m2 ?? 0) + $totalArea, 6);
            if (!empty($existing[0]['page_dimensions'])) {
                $first = $existing[0]['page_dimensions'][0];
                $job->width = $first['width_mm'] ?? null;
                $job->height = $first['height_mm'] ?? null;
            }
            $job->save();

            // Updated job with dimensions

            return [
                'dimensions_breakdown' => $existing,
                'total_area_m2' => $job->total_area_m2,
                '__temp_pdf_path' => $tempPath,
            ];
        } finally {
            // Do not unlink here; allow thumbnail job to reuse the temp PDF.
            // The GeneratePdfThumbnails job will clean it up when done.
        }
    }

    /**
     * Run the Node.js script to get PDF dimensions and return decoded array or null.
     */
    private function runNodePdfDimensions(string $pdfPath): ?array
    {
        $scriptPath = base_path('scripts/pdf-dimensions-accurate.cjs');
        if (!file_exists($scriptPath)) {
            \Log::error('PDF dimensions script not found', ['script_path' => $scriptPath]);
            return null;
        }

        // Execute with a simple shell call; stderr redirected to stdout
        // Capture ONLY stdout so JSON parsing isn't polluted by debug logs on stderr
        $command = "node \"$scriptPath\" \"$pdfPath\"";
        $output = shell_exec($command);
        if (!$output) {
            \Log::error('Node dimensions script returned no output', [
                'command' => $command,
            ]);
            return null;
        }

        $result = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error('Node dimensions script JSON parse failed', [
                'error' => json_last_error_msg(),
                'output' => $output,
            ]);
            return null;
        }
        // Attach raw output for optional debugging upstream
        $result['_raw_output'] = $output;
        return $result;
    }

    public function abort(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string',
            'uploadId' => 'required|string',
        ]);

        try {
            $s3 = $this->s3();
            $s3->abortMultipartUpload([
                'Bucket' => $this->bucket(),
                'Key' => $data['key'],
                'UploadId' => $data['uploadId'],
            ]);
            return response()->json(['message' => 'Upload aborted']);
        } catch (S3Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}


