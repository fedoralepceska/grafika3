<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use Inertia\Inertia;

class UploadController extends Controller
{
    public function showFileUploadPage()
    {
        return Inertia::render('FileUploader');
    }

    public function storeChunk(Request $request)
    {
        $chunkIndex = $request->chunk_index;
        $totalChunks = $request->total_chunks;
        $fileExtension = $request->file_extension;
        $filename = Str::slug($request->filename);
        $file = $request->file;

        if ($chunkIndex === $totalChunks - 1) {
            $this->assembleAndStoreFile($filename, $totalChunks, $fileExtension);
        } else {
            $this->storeTemporaryChunk($file, $filename, $chunkIndex);
        }
        $this->assembleAndStoreFile($filename, $totalChunks, $fileExtension);
        return response()->json(['message' => 'Chunk uploaded successfully']);
    }

    private function storeTemporaryChunk($file, $filename, $chunkIndex)
    {
        $tempFilename = "{$filename}.chunk.{$chunkIndex}";
        $file->storeAs('uploads/chunks', $tempFilename);
    }

    private function assembleAndStoreFile($filename, $totalChunks, $fileExtension)
    {
        if ($fileExtension === 'tiff' || $fileExtension === 'tif') {
            // Use GD Library or other specialized method for TIFF assembly
            $this->assembleTiffFile($filename, $totalChunks);
        }
        else {
            $assembledFilePath = Storage::disk('local')->path("uploads/originalFile/{$filename}");

            if (!File::isWritable(dirname($assembledFilePath))) {
                // Check if the directory is writable
                Log::error("Error assembling file: The 'originalFile' directory is not writable.");
                return;
            }

            try {
                // Check if all required chunks exist
                for ($i = 0; $i < $totalChunks; $i++) {
                    $chunkPath = storage_path("app/uploads/chunks/{$filename}.chunk.{$i}");
                    if (!File::exists($chunkPath)) {
                        Log::error("Error assembling file: Chunk {$i} is missing.");
                        return;
                    }
                }

                Storage::disk('local')->put("uploads/originalFile/{$filename}", "");

                for ($i = 0; $i < $totalChunks; $i++) {
                    $chunkPath = storage_path("app/uploads/chunks/{$filename}.chunk.{$i}");

                    // Check the content of the chunk before appending
                    $chunkContent = File::get($chunkPath);
                    Log::info("Chunk {$i} content: " . $chunkContent);

                    // Use the correct filename for each chunk
                    Storage::disk('local')->append("uploads/originalFile/{$filename}.{$fileExtension}", $chunkContent);
                }
//                // Delete temporary chunks
//                for ($i = 0; $i < $totalChunks; $i++) {
//                    Storage::disk('local')->delete("uploads/chunks/{$filename}.chunk.{$i}");
//                }

                Log::info("File assembled successfully at: {$assembledFilePath}");
            } catch (\Exception $e) {
                Log::error("Error assembling file: " . $e->getMessage());
            }
        }
    }
    private function assembleTiffFile($filename, $totalChunks)
    {
        $imagick = new Imagick();

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = storage_path("app/uploads/chunks/{$filename}.chunk.{$i}");
            $imagick->readImage($chunkPath);
        }

        $assembledFilePath = storage_path("app/uploads/originalFile/{$filename}.tiff");
        $imagick->writeImages($assembledFilePath, true);

        // Delete temporary chunks
        for ($i = 0; $i < $totalChunks; $i++) {
            Storage::disk('local')->delete("uploads/chunks/{$filename}.chunk.{$i}");
        }

        Log::info("TIFF file assembled successfully at: {$assembledFilePath}");
    }
}
