<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $filename = Str::slug($request->filename);
        $file = $request->file;

        // Validate input (file type, size) here

        if ($chunkIndex === $totalChunks - 1) {
            // Assemble the final file in the "originalFile" directory
            $assembledFilePath = storage_path("app/uploads/originalFile/{$filename}");
            $assembledFile = Storage::put($assembledFilePath, "");

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = storage_path("uploads/chunks/{$filename}.chunk.{$i}");
                Storage::append($assembledFile, File::get($chunkPath));
                Storage::delete("uploads/chunks/{$filename}.chunk.{$i}"); // Clean up chunks
            }
        } else {
            // Store temporary chunks
            $tempFilename = "{$filename}.chunk.{$chunkIndex}";
            $file->storeAs('uploads/chunks', $tempFilename);
        }

        return response()->json(['message' => 'Chunk uploaded successfully']);
    }

}
