<?php

namespace App\Jobs;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GeneratePdfThumbnails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900; // up to 15 minutes for large PDFs

    public function __construct(
        public int $jobId,
        public string $r2Key,
        public ?string $tempLocalPath = null,
        public int $dpi = 72, // effective low DPI; adjust if needed
        public ?int $fileIndex = null,
        public bool $isCuttingFile = false
    ) {}

    public function handle(): void
    {
        try {
            // Add basic logging to catch any early failures
            \Log::info('GeneratePdfThumbnails: job starting', ['job_id' => $this->jobId]);
            
            $job = Job::find($this->jobId);
            if (!$job) {
                \Log::warning('GeneratePdfThumbnails: job not found', ['job_id' => $this->jobId]);
                return;
            }

            \Log::channel('stderr')->info('GeneratePdfThumbnails: start', [
                'job_id' => $job->id,
                'r2_key' => $this->r2Key,
                'dpi' => $this->dpi,
            ]);
        } catch (\Throwable $e) {
            \Log::error('GeneratePdfThumbnails: early failure', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // Resolve source PDF path
        $originalPdfPath = $this->ensureLocalPdf();
        if (!$originalPdfPath || !file_exists($originalPdfPath)) {
            \Log::warning('GeneratePdfThumbnails: source PDF unavailable', [
                'job_id' => $job->id,
                'key' => $this->r2Key,
                'source' => $originalPdfPath,
            ]);
            return;
        }

        // Skip PDF scaling since pdf-lib doesn't reduce file size significantly
        // Use original PDF directly - thumbnail generation tools handle large files well at low DPI
        $sourcePath = $originalPdfPath;
        
        \Log::channel('stderr')->info('GeneratePdfThumbnails: using original PDF directly', [
            'job_id' => $job->id,
            'scaling_skipped' => true,
            'reason' => 'pdf-lib scaling does not reduce file size significantly',
            'source_for_thumbnails' => $sourcePath,
        ]);

        \Log::channel('stderr')->info('GeneratePdfThumbnails: using source PDF', [
            'job_id' => $job->id,
            'source_path' => $sourcePath,
            'is_scaled' => false,
            'file_exists' => file_exists($sourcePath),
            'file_size' => file_exists($sourcePath) ? filesize($sourcePath) : 0,
            'is_readable' => file_exists($sourcePath) ? is_readable($sourcePath) : false,
        ]);

        $workDir = storage_path('app/temp/thumbs_' . $job->id . '_' . time());
        if (!is_dir($workDir)) {
            mkdir($workDir, 0755, true);
        }

        // Use Poppler's pdftocairo to render pages to PNG at low DPI
        // Output pattern: thumb-1.png, thumb-2.png, ... in $workDir
        $outputPrefix = $workDir . DIRECTORY_SEPARATOR . 'thumb';
        // Resolve pdftocairo executable: prefer POPPLER_BIN env/config, fallback to PATH
        $popplerBin = env('POPPLER_BIN') ?: config('services.poppler_bin');
        $exe = 'pdftocairo';
        if (DIRECTORY_SEPARATOR === '\\') {
            $exe .= '.exe';
        }
        
        $finalExe = $exe; // Default to just the executable name
        
        if (!empty($popplerBin)) {
            $candidate = rtrim($popplerBin, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $exe;
            if (file_exists($candidate)) {
                $finalExe = '"' . $candidate . '"';
                \Log::channel('stderr')->info('GeneratePdfThumbnails: using configured pdftocairo', [
                    'job_id' => $job->id,
                    'path' => $candidate,
                ]);
            } else {
                \Log::warning('GeneratePdfThumbnails: configured pdftocairo not found', [
                    'job_id' => $job->id,
                    'expected_path' => $candidate,
                ]);
            }
        }
        
        // Test if pdftocairo is accessible
        $testOutput = [];
        $testExitCode = 0;
        @exec($finalExe . ' -v 2>&1', $testOutput, $testExitCode);
        
        if ($testExitCode !== 0) {
            \Log::error('GeneratePdfThumbnails: pdftocairo not accessible', [
                'job_id' => $job->id,
                'exe' => $finalExe,
                'test_output' => $testOutput,
                'exit_code' => $testExitCode,
            ]);
            

            
            $tempFilesToClean = [];
            if ($this->tempLocalPath && file_exists($this->tempLocalPath)) {
                $tempFilesToClean[] = $this->tempLocalPath;
            }
            $this->cleanup($workDir, $tempFilesToClean);
            return;
        }
        // Verify source PDF still exists before thumbnail generation
        \Log::channel('stderr')->info('GeneratePdfThumbnails: about to generate thumbnails', [
            'job_id' => $job->id,
            'source_path' => $sourcePath,
            'source_exists' => file_exists($sourcePath),
            'source_size' => file_exists($sourcePath) ? filesize($sourcePath) : 0,
            'work_dir' => $workDir,
            'work_dir_exists' => is_dir($workDir),
        ]);
        
        // Use pdf-poppler (Node.js) as primary method - it's the most reliable
        $success = false;
        
        \Log::channel('stderr')->info('GeneratePdfThumbnails: starting with pdf-poppler as primary method', [
            'job_id' => $job->id,
            'source_path' => $sourcePath,
            'work_dir' => $workDir,
        ]);
        
        // Try pdf-poppler first (pure Node.js, no external dependencies)
        $success = $this->tryPdf2Pic($sourcePath, $workDir, $job->id);
        
        if (!$success && extension_loaded('imagick')) {
            // Fallback to ImageMagick if available
            $success = $this->tryImageMagickThumbnails($sourcePath, $workDir, $job->id);
        }
        
        if (!$success) {
            // Fallback to pdftocairo with A4 paper size
            \Log::channel('stderr')->info('GeneratePdfThumbnails: pdftoppm failed, trying pdftocairo with A4 standardization', [
                'job_id' => $job->id,
                'source_still_exists' => file_exists($sourcePath),
                'target_format' => 'A4 (595x842px)',
            ]);
            
            // Build a cross-platform command with A4 paper size constraint
            // Using PNG format with A4 paper size (210x297mm)
            if (DIRECTORY_SEPARATOR === '\\') {
                $command = $finalExe . ' -png -r ' . (int) $this->dpi . ' -paper A4 ' . '"' . $sourcePath . '" ' . '"' . $outputPrefix . '"';
            } else {
                $command = $finalExe . ' -png -r ' . escapeshellarg((string) $this->dpi) . ' -paper A4 ' . escapeshellarg($sourcePath) . ' ' . escapeshellarg($outputPrefix);
            }
            
            try {
                $this->execCommand($command);
                
                // Wait a moment for file system to sync on Windows
                usleep(500000); // 0.5 seconds
                
                // Check if files were actually created
                $files = glob($workDir . DIRECTORY_SEPARATOR . 'thumb*.png');
                if (count($files) > 0) {
                    $success = true;
                    \Log::channel('stderr')->info('GeneratePdfThumbnails: pdftocairo succeeded', [
                        'job_id' => $job->id,
                        'files_created' => count($files),
                    ]);
                } else {
                    \Log::channel('stderr')->error('GeneratePdfThumbnails: pdftocairo executed but no files created', [
                        'job_id' => $job->id,
                        'command' => $command,
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::channel('stderr')->error('GeneratePdfThumbnails: both pdftoppm and pdftocairo failed', [
                    'job_id' => $job->id,
                    'pdftocairo_command' => $command,
                    'pdftocairo_error' => $e->getMessage(),
                    'source_exists' => file_exists($sourcePath),
                    'source_size' => file_exists($sourcePath) ? filesize($sourcePath) : 0,
                    'work_dir_exists' => is_dir($workDir),
                ]);
            }
        }
        
        if (!$success) {
            $tempFilesToClean = [];
            if ($this->tempLocalPath && file_exists($this->tempLocalPath)) {
                $tempFilesToClean[] = $this->tempLocalPath;
            }
            $this->cleanup($workDir, $tempFilesToClean);
            return;
        }

        // Verify source PDF exists and is readable before running pdftocairo
        if (!file_exists($sourcePath)) {
            \Log::channel('stderr')->error('GeneratePdfThumbnails: source PDF file does not exist', [
                'job_id' => $job->id,
                'source_path' => $sourcePath,
            ]);
            $tempFilesToClean = [];
            if ($this->tempLocalPath && file_exists($this->tempLocalPath)) {
                $tempFilesToClean[] = $this->tempLocalPath;
            }
            $this->cleanup($workDir, $tempFilesToClean);
            return;
        }

        // Skip PDF validation - if ImageMagick can read it, it's valid

        // Upload thumbnails and cleanup
        $this->uploadThumbnails($workDir, $job);

        // Clean up temp files
        $tempFilesToClean = [];
        if ($this->tempLocalPath && file_exists($this->tempLocalPath)) {
            $tempFilesToClean[] = $this->tempLocalPath;
        }
        
        $this->cleanup($workDir, $tempFilesToClean);
    }

    private function ensureLocalPdf(): ?string
    {
        if ($this->tempLocalPath && file_exists($this->tempLocalPath)) {
            return $this->tempLocalPath;
        }
        
        // Download from R2 if needed with retry logic for eventual consistency
        $maxRetries = 3;
        $retryDelay = 2; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $disk = Storage::disk('r2');
                
                \Log::info('GeneratePdfThumbnails: attempting to download from R2', [
                    'job_id' => $this->jobId,
                    'key' => $this->r2Key,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries
                ]);
                
                if (!$disk->exists($this->r2Key)) {
                    if ($attempt < $maxRetries) {
                        \Log::warning('GeneratePdfThumbnails: file not found in R2, retrying', [
                            'job_id' => $this->jobId,
                            'key' => $this->r2Key,
                            'attempt' => $attempt,
                            'retry_in_seconds' => $retryDelay
                        ]);
                        sleep($retryDelay);
                        continue;
                    }
                    return null;
                }
                
                $stream = $disk->readStream($this->r2Key);
                if (!$stream) {
                    if ($attempt < $maxRetries) {
                        \Log::warning('GeneratePdfThumbnails: failed to get stream from R2, retrying', [
                            'job_id' => $this->jobId,
                            'key' => $this->r2Key,
                            'attempt' => $attempt
                        ]);
                        sleep($retryDelay);
                        continue;
                    }
                    return null;
                }
                
                $dest = storage_path('app/temp/thumbsrc_' . $this->jobId . '_' . time() . '.pdf');
                if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                $out = fopen($dest, 'wb');
                stream_copy_to_stream($stream, $out);
                fclose($out);
                if (is_resource($stream)) fclose($stream);
                
                \Log::info('GeneratePdfThumbnails: successfully downloaded from R2', [
                    'job_id' => $this->jobId,
                    'key' => $this->r2Key,
                    'local_path' => $dest,
                    'file_size' => filesize($dest),
                    'attempt' => $attempt
                ]);
                
                return $dest;
                
            } catch (\Throwable $e) {
                if ($attempt < $maxRetries) {
                    \Log::warning('GeneratePdfThumbnails: download attempt failed, retrying', [
                        'job_id' => $this->jobId,
                        'key' => $this->r2Key,
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                        'retry_in_seconds' => $retryDelay
                    ]);
                    sleep($retryDelay);
                    continue;
                } else {
                    \Log::error('GeneratePdfThumbnails: failed to download source from R2 after all retries', [
                        'job_id' => $this->jobId,
                        'key' => $this->r2Key,
                        'error' => $e->getMessage(),
                        'attempts' => $maxRetries
                    ]);
                    return null;
                }
            }
        }
        
        return null;
    }

    private function execCommand(string $command): void
    {
        // Simple exec() approach that works with Node.js
        $output = [];
        $exitCode = 0;
        
        \Log::channel('stderr')->info('GeneratePdfThumbnails: executing command', [ 'command' => $command ]);
        
        // Increase PHP execution time for thumbnail generation
        $originalTimeLimit = ini_get('max_execution_time');
        set_time_limit(300); // 5 minutes for problematic PDFs
        
        exec($command . ' 2>&1', $output, $exitCode);
        
        // Restore original time limit
        set_time_limit($originalTimeLimit);
        
        \Log::channel('stderr')->info('GeneratePdfThumbnails: command result', [
            'command' => $command,
            'exit_code' => $exitCode,
            'output_lines' => count($output),
            'output' => implode("\n", $output),
            'current_working_dir' => getcwd(),
        ]);
        
        if ($exitCode !== 0) {
            throw new \RuntimeException('Command failed: ' . $command . ' | Exit Code: ' . $exitCode . ' | Output: ' . implode("\n", $output));
        }
    }

    private function execCommandWithTimeout(string $command, int $timeoutSeconds): void
    {
        \Log::channel('stderr')->info('GeneratePdfThumbnails: executing command with timeout', [
            'command' => $command,
            'timeout' => $timeoutSeconds
        ]);
        
        $startTime = time();
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes);
        
        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start process: ' . $command);
        }
        
        fclose($pipes[0]); // Close stdin
        
        $output = '';
        $error = '';
        
        // Set streams to non-blocking
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        while (true) {
            $status = proc_get_status($process);
            
            // Check timeout
            if (time() - $startTime > $timeoutSeconds) {
                proc_terminate($process);
                proc_close($process);
                throw new \RuntimeException("Command timed out after {$timeoutSeconds} seconds: " . $command);
            }
            
            // Read output
            $output .= stream_get_contents($pipes[1]);
            $error .= stream_get_contents($pipes[2]);
            
            // Check if process finished
            if (!$status['running']) {
                break;
            }
            
            usleep(100000); // 0.1 second
        }
        
        // Get final output
        $output .= stream_get_contents($pipes[1]);
        $error .= stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $exitCode = proc_close($process);
        
        \Log::channel('stderr')->info('GeneratePdfThumbnails: command result with timeout', [
            'command' => $command,
            'exit_code' => $exitCode,
            'output' => $output,
            'error' => $error,
            'execution_time' => time() - $startTime,
        ]);
        
        if ($exitCode !== 0) {
            throw new \RuntimeException('Command failed: ' . $command . ' | Exit Code: ' . $exitCode . ' | Output: ' . $output . ' | Error: ' . $error);
        }
    }

    private function createScaledPdf(string $originalPdfPath, int $jobId): ?string
    {
        $scaledPdfPath = storage_path('app/temp/pdfdim_' . $jobId . '_' . time() . '.pdf');
        
        // Try pdf-lib Node.js script first (most memory efficient)
        try {
            $scaledPath = $this->createScaledPdfWithPdfLib($originalPdfPath, $scaledPdfPath, $jobId);
            if ($scaledPath) {
                return $scaledPath;
            }
        } catch (\Throwable $e) {
            \Log::warning('GeneratePdfThumbnails: pdf-lib scaling failed', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
        }
        
        // Fallback to ImageMagick
        if (extension_loaded('imagick')) {
            try {
                $scaledPath = $this->createScaledPdfWithImageMagick($originalPdfPath, $scaledPdfPath, $jobId);
                if ($scaledPath) {
                    return $scaledPath;
                }
            } catch (\Throwable $e) {
                \Log::warning('GeneratePdfThumbnails: ImageMagick PDF scaling failed', [
                    'job_id' => $jobId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Fallback to direct Ghostscript
        $gsCommand = $this->buildGhostscriptCommand($originalPdfPath, $scaledPdfPath);
        
        if ($gsCommand) {
            \Log::channel('stderr')->info('GeneratePdfThumbnails: attempting to create scaled PDF with Ghostscript', [
                'job_id' => $jobId,
                'command' => $gsCommand,
            ]);
            
            try {
                $this->execCommand($gsCommand);
                if (file_exists($scaledPdfPath)) {
                    \Log::channel('stderr')->info('GeneratePdfThumbnails: created scaled PDF with Ghostscript', [
                        'job_id' => $jobId,
                        'original_size' => filesize($originalPdfPath),
                        'scaled_size' => filesize($scaledPdfPath),
                        'scaled_path' => $scaledPdfPath,
                    ]);
                    return $scaledPdfPath;
                } else {
                    \Log::warning('GeneratePdfThumbnails: scaled PDF file not created', [
                        'job_id' => $jobId,
                        'expected_path' => $scaledPdfPath,
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('GeneratePdfThumbnails: failed to create scaled PDF with Ghostscript', [
                    'job_id' => $jobId,
                    'error' => $e->getMessage(),
                    'command' => $gsCommand,
                ]);
            }
        } else {
            \Log::info('GeneratePdfThumbnails: Ghostscript not available, will use original PDF', [
                'job_id' => $jobId,
            ]);
        }
        
        return null; // Return null to use original PDF
    }

    private function createScaledPdfWithPdfLib(string $originalPdfPath, string $scaledPdfPath, int $jobId): ?string
    {
        try {
            \Log::channel('stderr')->info('GeneratePdfThumbnails: attempting to create scaled PDF with pdf-lib', [
                'job_id' => $jobId,
                'original_path' => $originalPdfPath,
                'original_exists' => file_exists($originalPdfPath),
                'original_size' => file_exists($originalPdfPath) ? filesize($originalPdfPath) : 0,
                'scaled_path' => $scaledPdfPath,
            ]);

            // Verify original PDF exists and is readable
            if (!file_exists($originalPdfPath)) {
                \Log::warning('GeneratePdfThumbnails: original PDF missing for scaling', [
                    'job_id' => $jobId,
                    'original_path' => $originalPdfPath,
                ]);
                return null;
            }

            // Build the Node.js command
            $scriptPath = base_path('scripts/scale-pdf.js');
            $scaleFactor = 0.1; // Scale down to 10% for thumbnails (1:10 ratio)
            
            if (!file_exists($scriptPath)) {
                \Log::warning('GeneratePdfThumbnails: pdf-lib script not found', [
                    'job_id' => $jobId,
                    'script_path' => $scriptPath,
                ]);
                return null;
            }
            
            // Ensure output directory exists
            $outputDir = dirname($scaledPdfPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            // Build command for Windows - use full path to node if needed
            $nodeExe = 'node';
            if (DIRECTORY_SEPARATOR === '\\') {
                // Try to find node.exe in common locations
                $nodePaths = [
                    'C:\\Program Files\\nodejs\\node.exe',
                    'C:\\Program Files (x86)\\nodejs\\node.exe',
                    'node' // fallback to PATH
                ];
                
                foreach ($nodePaths as $path) {
                    if ($path === 'node' || file_exists($path)) {
                        $nodeExe = $path;
                        break;
                    }
                }
                
                $command = '"' . $nodeExe . '" "' . $scriptPath . '" "' . $originalPdfPath . '" "' . $scaledPdfPath . '" ' . $scaleFactor;
            } else {
                $command = 'node ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($originalPdfPath) . ' ' . escapeshellarg($scaledPdfPath) . ' ' . $scaleFactor;
            }
            
            \Log::channel('stderr')->info('GeneratePdfThumbnails: executing pdf-lib command', [
                'job_id' => $jobId,
                'command' => $command,
            ]);
            
            // Execute the Node.js script
            $output = [];
            $exitCode = 0;
            exec($command . ' 2>&1', $output, $exitCode);
            
            if ($exitCode === 0 && file_exists($scaledPdfPath)) {
                \Log::channel('stderr')->info('GeneratePdfThumbnails: created scaled PDF with pdf-lib', [
                    'job_id' => $jobId,
                    'original_size' => filesize($originalPdfPath),
                    'scaled_size' => filesize($scaledPdfPath),
                    'scaled_path' => $scaledPdfPath,
                    'output' => implode("\n", $output),
                ]);
                return $scaledPdfPath;
            } else {
                \Log::warning('GeneratePdfThumbnails: pdf-lib scaling failed', [
                    'job_id' => $jobId,
                    'exit_code' => $exitCode,
                    'output' => implode("\n", $output),
                    'file_exists' => file_exists($scaledPdfPath),
                    'original_still_exists' => file_exists($originalPdfPath),
                ]);
            }
            
        } catch (\Throwable $e) {
            \Log::warning('GeneratePdfThumbnails: pdf-lib scaling exception', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'original_path' => $originalPdfPath,
                'original_exists' => file_exists($originalPdfPath),
            ]);
        }
        
        return null;
    }

    private function createScaledPdfWithImageMagick(string $originalPdfPath, string $scaledPdfPath, int $jobId): ?string
    {
        try {
            \Log::channel('stderr')->info('GeneratePdfThumbnails: attempting to create scaled PDF with ImageMagick', [
                'job_id' => $jobId,
                'original_path' => $originalPdfPath,
                'scaled_path' => $scaledPdfPath,
            ]);

            $imagick = new \Imagick();
            
            // Set resolution to 72 DPI for smaller file size
            $imagick->setResolution(72, 72);
            
            // Set compression quality for smaller file size
            $imagick->setImageCompressionQuality(60);
            
            // Read the PDF
            $imagick->readImage($originalPdfPath);
            
            // Process each page to reduce size
            foreach ($imagick as $page) {
                // Reduce image quality and size
                $page->setImageCompressionQuality(60);
                $page->setImageFormat('pdf');
                
                // Resample to lower DPI if the image is very large
                $geometry = $page->getImageGeometry();
                if ($geometry['width'] > 2000 || $geometry['height'] > 2000) {
                    // Scale down large pages (like your 4000mm x 4000mm pages)
                    $page->resampleImage(72, 72, \Imagick::FILTER_LANCZOS, 1);
                }
            }
            
            // Write the scaled PDF
            $imagick->writeImages($scaledPdfPath, true);
            $imagick->clear();
            $imagick->destroy();
            
            if (file_exists($scaledPdfPath)) {
                \Log::channel('stderr')->info('GeneratePdfThumbnails: created scaled PDF with ImageMagick', [
                    'job_id' => $jobId,
                    'original_size' => filesize($originalPdfPath),
                    'scaled_size' => filesize($scaledPdfPath),
                    'scaled_path' => $scaledPdfPath,
                ]);
                return $scaledPdfPath;
            }
            
        } catch (\Throwable $e) {
            \Log::warning('GeneratePdfThumbnails: ImageMagick PDF scaling failed', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'original_path' => $originalPdfPath,
            ]);
            
            // Clean up if something went wrong
            if (isset($imagick)) {
                try {
                    $imagick->clear();
                    $imagick->destroy();
                } catch (\Throwable $cleanupError) {
                    // Ignore cleanup errors
                }
            }
        }
        
        return null;
    }

    private function buildGhostscriptCommand(string $inputPdf, string $outputPdf): ?string
    {
        // Look for Ghostscript executable
        $gsExecutables = ['gs', 'gswin64c', 'gswin32c'];
        $gsPath = null;
        
        foreach ($gsExecutables as $exe) {
            if (DIRECTORY_SEPARATOR === '\\') {
                $exe .= '.exe';
            }
            
            // Check if executable exists in PATH
            $whereCmd = DIRECTORY_SEPARATOR === '\\' ? 'where' : 'which';
            $output = [];
            @exec($whereCmd . ' ' . $exe . ' 2>&1', $output, $exitCode);
            
            if ($exitCode === 0 && !empty($output)) {
                $gsPath = $exe;
                break;
            }
        }
        
        if (!$gsPath) {
            \Log::info('GeneratePdfThumbnails: Ghostscript not found, using original PDF');
            return null;
        }
        
        // Build Ghostscript command for creating a heavily downscaled PDF suitable for thumbnails
        // Using /ebook setting for more aggressive compression and smaller file size
        if (DIRECTORY_SEPARATOR === '\\') {
            return $gsPath . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook ' .
                   '-dDownsampleColorImages=true -dColorImageResolution=72 ' .
                   '-dDownsampleGrayImages=true -dGrayImageResolution=72 ' .
                   '-dDownsampleMonoImages=true -dMonoImageResolution=72 ' .
                   '-dNOPAUSE -dQUIET -dBATCH -sOutputFile="' . $outputPdf . '" "' . $inputPdf . '"';
        } else {
            return $gsPath . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook ' .
                   '-dDownsampleColorImages=true -dColorImageResolution=72 ' .
                   '-dDownsampleGrayImages=true -dGrayImageResolution=72 ' .
                   '-dDownsampleMonoImages=true -dMonoImageResolution=72 ' .
                   '-dNOPAUSE -dQUIET -dBATCH -sOutputFile=' . escapeshellarg($outputPdf) . ' ' . escapeshellarg($inputPdf);
        }
    }



    private function tryPdfToPpm(string $sourcePath, string $workDir, int $jobId): bool
    {
        try {
            // Verify source file exists right before execution
            if (!file_exists($sourcePath)) {
                \Log::channel('stderr')->error('GeneratePdfThumbnails: source PDF missing at pdftoppm execution', [
                    'job_id' => $jobId,
                    'source_path' => $sourcePath,
                ]);
                return false;
            }
            
            // Try pdftoppm (alternative to pdftocairo)
            $popplerBin = env('POPPLER_BIN') ?: config('services.poppler_bin');
            $exe = 'pdftoppm';
            if (DIRECTORY_SEPARATOR === '\\') {
                $exe .= '.exe';
            }
            
            $finalExe = $exe;
            if (!empty($popplerBin)) {
                $candidate = rtrim($popplerBin, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $exe;
                if (file_exists($candidate)) {
                    $finalExe = '"' . $candidate . '"';
                }
            }
            
            $outputPrefix = $workDir . DIRECTORY_SEPARATOR . 'thumb';
            
            // Build pdftoppm command
            if (DIRECTORY_SEPARATOR === '\\') {
                $command = $finalExe . ' -png -r ' . (int) $this->dpi . ' "' . $sourcePath . '" "' . $outputPrefix . '"';
            } else {
                $command = $finalExe . ' -png -r ' . escapeshellarg((string) $this->dpi) . ' ' . escapeshellarg($sourcePath) . ' ' . escapeshellarg($outputPrefix);
            }
            
            // Test if we can write to the work directory
            $testFile = $workDir . DIRECTORY_SEPARATOR . 'test.txt';
            $canWrite = @file_put_contents($testFile, 'test');
            if ($canWrite) {
                @unlink($testFile);
            }
            
            \Log::channel('stderr')->info('GeneratePdfThumbnails: trying pdftoppm', [
                'job_id' => $jobId,
                'command' => $command,
                'source_exists' => file_exists($sourcePath),
                'source_size' => filesize($sourcePath),
                'source_readable' => is_readable($sourcePath),
                'work_dir_exists' => is_dir($workDir),
                'work_dir_writable' => is_writable($workDir),
                'can_write_test_file' => $canWrite !== false,
                'php_user' => get_current_user(),
                'work_dir_permissions' => substr(sprintf('%o', fileperms($workDir)), -4),
            ]);
            
            // Try with current working directory change to avoid long path issues
            $originalCwd = getcwd();
            $success = false;
            
            try {
                // Change to work directory
                chdir($workDir);
                
                // Build command with relative paths
                $relativeCommand = $finalExe . ' -png -r ' . (int) $this->dpi . ' "' . $sourcePath . '" "thumb"';
                
                \Log::channel('stderr')->info('GeneratePdfThumbnails: trying pdftoppm with relative paths', [
                    'job_id' => $jobId,
                    'relative_command' => $relativeCommand,
                    'current_dir' => getcwd(),
                ]);
                
                $this->execCommand($relativeCommand);
                
                // Wait a moment for file system to sync on Windows
                usleep(500000); // 0.5 seconds
                
                // Check if files were created
                $files = glob('thumb*.png');
                if (count($files) > 0) {
                    \Log::channel('stderr')->info('GeneratePdfThumbnails: pdftoppm succeeded with relative paths', [
                        'job_id' => $jobId,
                        'files_created' => count($files),
                        'files' => $files,
                    ]);
                    $success = true;
                }
            } finally {
                // Always restore original working directory
                chdir($originalCwd);
            }
            
            if ($success) {
                return true;
            }
            
            // Fallback to original command if relative paths didn't work
            \Log::channel('stderr')->info('GeneratePdfThumbnails: trying pdftoppm with absolute paths', [
                'job_id' => $jobId,
            ]);
            
            $this->execCommand($command);
            
            // Wait a moment for file system to sync on Windows
            usleep(500000); // 0.5 seconds
            
            // Check if files were created
            $files = glob($workDir . DIRECTORY_SEPARATOR . 'thumb*.png');
            if (count($files) > 0) {
                \Log::channel('stderr')->info('GeneratePdfThumbnails: pdftoppm succeeded with absolute paths', [
                    'job_id' => $jobId,
                    'files_created' => count($files),
                    'files' => array_map('basename', $files),
                ]);
                return true;
            } else {
                \Log::channel('stderr')->warning('GeneratePdfThumbnails: pdftoppm executed but no files created', [
                    'job_id' => $jobId,
                    'work_dir_contents' => array_map('basename', glob($workDir . DIRECTORY_SEPARATOR . '*')),
                ]);
            }
            
        } catch (\Throwable $e) {
            \Log::warning('GeneratePdfThumbnails: pdftoppm failed with exception', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'source_exists' => file_exists($sourcePath),
            ]);
        }
        
        return false;
    }

    private function tryPdf2Pic(string $sourcePath, string $workDir, int $jobId): bool
    {
        try {
            \Log::channel('stderr')->info('GeneratePdfThumbnails: trying pdf-poppler with A4 standardization', [
                'job_id' => $jobId,
                'source_path' => $sourcePath,
                'source_exists' => file_exists($sourcePath),
                'source_size' => filesize($sourcePath),
                'work_dir' => $workDir,
                'dpi' => $this->dpi,
                'target_format' => 'A4 standardized if canvas available',
            ]);

            // Build the Node.js command for pdf-poppler (now with A4 standardization built-in)
            $scriptPath = base_path('scripts/generate-thumbnails-poppler.cjs');
            
            if (!file_exists($scriptPath)) {
                \Log::warning('GeneratePdfThumbnails: pdf-poppler script not found', [
                    'job_id' => $jobId,
                    'script_path' => $scriptPath,
                ]);
                return false;
            }
            
            // Use the same Node.js path detection as PDF scaling
            $nodeExe = 'node';
            if (DIRECTORY_SEPARATOR === '\\') {
                $nodePaths = [
                    'C:\\Program Files\\nodejs\\node.exe',
                    'C:\\Program Files (x86)\\nodejs\\node.exe',
                    'node'
                ];
                
                foreach ($nodePaths as $path) {
                    if ($path === 'node' || file_exists($path)) {
                        $nodeExe = $path;
                        break;
                    }
                }
                
                // Set POPPLER_BIN environment variable for the Node.js script
                $popplerBin = env('POPPLER_BIN') ?: config('services.poppler_bin');
                $envPrefix = $popplerBin ? 'set POPPLER_BIN=' . $popplerBin . ' && ' : '';
                $command = $envPrefix . '"' . $nodeExe . '" "' . $scriptPath . '" "' . $sourcePath . '" "' . $workDir . '" ' . (int) $this->dpi;
            } else {
                // Set POPPLER_BIN environment variable for the Node.js script
                $popplerBin = env('POPPLER_BIN') ?: config('services.poppler_bin');
                $envPrefix = $popplerBin ? 'POPPLER_BIN=' . escapeshellarg($popplerBin) . ' ' : '';
                $command = $envPrefix . 'node ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($sourcePath) . ' ' . escapeshellarg($workDir) . ' ' . (int) $this->dpi;
            }
            
            \Log::channel('stderr')->info('GeneratePdfThumbnails: executing pdf-poppler command', [
                'job_id' => $jobId,
                'command' => $command,
            ]);
            
            // Use regular exec for pdf-poppler (no timeout - let it complete)
            $this->execCommand($command);
            
            // Wait a moment for files to be written
            usleep(500000); // 0.5 seconds
            
            // Check if files were created
            $files = glob($workDir . DIRECTORY_SEPARATOR . 'thumb*.png');
            if (count($files) > 0) {
                \Log::channel('stderr')->info('GeneratePdfThumbnails: pdf-poppler succeeded', [
                    'job_id' => $jobId,
                    'files_created' => count($files),
                    'files' => array_map('basename', $files),
                ]);
                return true;
            } else {
                \Log::channel('stderr')->warning('GeneratePdfThumbnails: pdf-poppler executed but no files created', [
                    'job_id' => $jobId,
                    'work_dir_contents' => array_map('basename', glob($workDir . DIRECTORY_SEPARATOR . '*')),
                ]);
            }
            
        } catch (\Throwable $e) {
            \Log::channel('stderr')->warning('GeneratePdfThumbnails: pdf-poppler failed', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'source_exists' => file_exists($sourcePath),
            ]);
        }
        
        return false;
    }

    private function tryImageMagickThumbnails(string $sourcePath, string $workDir, int $jobId): bool
    {
        try {
            \Log::channel('stderr')->info('GeneratePdfThumbnails: trying ImageMagick with A4 standardization', [
                'job_id' => $jobId,
                'source_path' => $sourcePath,
                'source_exists' => file_exists($sourcePath),
                'source_size' => filesize($sourcePath),
                'work_dir' => $workDir,
                'target_format' => 'A4 (595x842px)',
            ]);

            // A4 dimensions at 72 DPI
            $a4Width = 595;  // 210mm at 72 DPI
            $a4Height = 842; // 297mm at 72 DPI

            $imagick = new \Imagick();
            
            // Set resolution for thumbnail generation
            $imagick->setResolution($this->dpi, $this->dpi);
            
            // Read the PDF
            $imagick->readImage($sourcePath);
            
            $pageCount = $imagick->getNumberImages();
            $filesCreated = 0;
            
            // Process each page
            foreach ($imagick as $pageIndex => $page) {
                $pageNumber = $pageIndex + 1;
                $outputPath = $workDir . DIRECTORY_SEPARATOR . 'thumb-' . $pageNumber . '.png';
                
                // Get original dimensions
                $originalWidth = $page->getImageWidth();
                $originalHeight = $page->getImageHeight();
                
                // Create A4 canvas
                $a4Canvas = new \Imagick();
                $a4Canvas->newImage($a4Width, $a4Height, 'white');
                $a4Canvas->setImageFormat('png');
                
                // Calculate scaling to fit within A4 while preserving aspect ratio
                $scaleX = $a4Width / $originalWidth;
                $scaleY = $a4Height / $originalHeight;
                $scale = min($scaleX, $scaleY); // Use smaller scale to fit entirely
                
                $scaledWidth = (int) ($originalWidth * $scale);
                $scaledHeight = (int) ($originalHeight * $scale);
                
                // Resize the original page
                $page->resizeImage($scaledWidth, $scaledHeight, \Imagick::FILTER_LANCZOS, 1);
                
                // Calculate centering offset
                $offsetX = (int) (($a4Width - $scaledWidth) / 2);
                $offsetY = (int) (($a4Height - $scaledHeight) / 2);
                
                // Composite the scaled image onto the A4 canvas
                $a4Canvas->compositeImage($page, \Imagick::COMPOSITE_OVER, $offsetX, $offsetY);
                
                // Set compression quality
                $a4Canvas->setImageCompressionQuality(85);
                
                // Write the A4 standardized thumbnail
                $a4Canvas->writeImage($outputPath);
                
                \Log::channel('stderr')->info('GeneratePdfThumbnails: ImageMagick A4 page processed', [
                    'job_id' => $jobId,
                    'page' => $pageNumber,
                    'original_size' => $originalWidth . 'x' . $originalHeight,
                    'a4_size' => $a4Width . 'x' . $a4Height,
                    'scale_factor' => round($scale, 3),
                    'centered_at' => $offsetX . ',' . $offsetY,
                ]);
                
                // Clean up page canvas
                $a4Canvas->clear();
                $a4Canvas->destroy();
                
                if (file_exists($outputPath)) {
                    $filesCreated++;
                    \Log::channel('stderr')->info('GeneratePdfThumbnails: ImageMagick created A4 thumbnail', [
                        'job_id' => $jobId,
                        'page' => $pageNumber,
                        'file' => basename($outputPath),
                        'size' => filesize($outputPath),
                    ]);
                }
            }
            
            $imagick->clear();
            $imagick->destroy();
            
            if ($filesCreated > 0) {
                \Log::channel('stderr')->info('GeneratePdfThumbnails: ImageMagick A4 standardization succeeded', [
                    'job_id' => $jobId,
                    'pages_processed' => $pageCount,
                    'files_created' => $filesCreated,
                    'standard_size' => '595x842px (A4)',
                ]);
                return true;
            } else {
                \Log::channel('stderr')->warning('GeneratePdfThumbnails: ImageMagick processed but no files created', [
                    'job_id' => $jobId,
                    'pages_processed' => $pageCount,
                ]);
            }
            
        } catch (\Throwable $e) {
            \Log::channel('stderr')->warning('GeneratePdfThumbnails: ImageMagick A4 standardization failed', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'source_exists' => file_exists($sourcePath),
            ]);
            
            // Clean up if something went wrong
            if (isset($imagick)) {
                try {
                    $imagick->clear();
                    $imagick->destroy();
                } catch (\Throwable $cleanupError) {
                    // Ignore cleanup errors
                }
            }
        }
        
        return false;
    }

    private function uploadThumbnails(string $workDir, $job): void
    {
        // Store PNG thumbnails directly in public/jobfiles
        $thumbnailFiles = glob($workDir . DIRECTORY_SEPARATOR . 'thumb*.png');
        
        if (empty($thumbnailFiles)) {
            \Log::channel('stderr')->warning('GeneratePdfThumbnails: no thumbnail files found', [
                'job_id' => $job->id,
                'work_dir' => $workDir,
                'files_in_dir' => glob($workDir . DIRECTORY_SEPARATOR . '*'),
                'is_cutting_file' => $this->isCuttingFile,
            ]);
            return;
        }
        
        // Create public thumbnails directory (different for cutting files)
        $thumbnailSubDir = $this->isCuttingFile ? 'cutting-thumbnails' : 'thumbnails';
        $publicThumbnailDir = public_path('jobfiles/' . $thumbnailSubDir . '/' . $job->id);
        if (!is_dir($publicThumbnailDir)) {
            mkdir($publicThumbnailDir, 0755, true);
        }
        
        $savedCount = 0;
        $originalFileName = pathinfo($this->r2Key, PATHINFO_FILENAME);
        
        sort($thumbnailFiles, SORT_NATURAL);
        foreach ($thumbnailFiles as $index => $thumbnailFile) {
            $pageNumber = $index + 1;
            
            // For cutting files, include file index in filename for proper identification
            if ($this->isCuttingFile && $this->fileIndex !== null) {
                $localFileName = "job_{$job->id}_{$this->fileIndex}_{$originalFileName}_page_{$pageNumber}.png";
            } else {
                $localFileName = $originalFileName . "_page_{$pageNumber}.png";
            }
            
            $localPath = $publicThumbnailDir . DIRECTORY_SEPARATOR . $localFileName;
            
            try {
                copy($thumbnailFile, $localPath);
                $savedCount++;
                
                \Log::channel('stderr')->info('GeneratePdfThumbnails: saved thumbnail to public', [
                    'job_id' => $job->id,
                    'local_path' => $localPath,
                    'page' => $pageNumber,
                    'is_cutting_file' => $this->isCuttingFile,
                    'file_index' => $this->fileIndex,
                    'web_url' => '/jobfiles/' . $thumbnailSubDir . '/' . $job->id . '/' . $localFileName,
                ]);
            } catch (\Exception $e) {
                \Log::channel('stderr')->error('GeneratePdfThumbnails: failed to save thumbnail to public', [
                    'job_id' => $job->id,
                    'source_file' => $thumbnailFile,
                    'target_path' => $localPath,
                    'is_cutting_file' => $this->isCuttingFile,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        \Log::channel('stderr')->info('GeneratePdfThumbnails complete', [
            'job_id' => $job->id,
            'source' => $this->r2Key,
            'saved_to_public' => $savedCount,
            'thumbnail_dir' => $publicThumbnailDir,
            'is_cutting_file' => $this->isCuttingFile,
        ]);

        // Clean up temp files
        $tempFilesToClean = [];
        if ($this->tempLocalPath && file_exists($this->tempLocalPath)) {
            $tempFilesToClean[] = $this->tempLocalPath;
        }
        
        $this->cleanup($workDir, $tempFilesToClean);
    }

    private function cleanup(string $workDir, array $tempFiles = []): void
    {
        \Log::channel('stderr')->info('GeneratePdfThumbnails: cleaning up temp files', [
            'work_dir' => $workDir,
            'work_dir_exists' => is_dir($workDir),
            'temp_files_count' => count($tempFiles),
        ]);
        
        // Remove temp files
        if (is_dir($workDir)) {
            foreach (glob($workDir . DIRECTORY_SEPARATOR . '*') as $f) {
                @unlink($f);
            }
            @rmdir($workDir);
        }
        
        // Remove additional temp files
        foreach ($tempFiles as $filePath) {
            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }
}


