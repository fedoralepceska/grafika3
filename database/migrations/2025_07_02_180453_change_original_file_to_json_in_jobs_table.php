<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, migrate existing data from string to JSON format
        $jobs = DB::table('jobs')->whereNotNull('originalFile')->get();
        
        foreach ($jobs as $job) {
            if (!empty($job->originalFile)) {
                // Convert single file path to JSON array format
                $fileArray = [$job->originalFile];
                DB::table('jobs')
                    ->where('id', $job->id)
                    ->update(['originalFile' => json_encode($fileArray)]);
            }
        }

        // Now change the column type to JSON
        Schema::table('jobs', function (Blueprint $table) {
            $table->json('originalFile')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, migrate JSON data back to string format (take first file only)
        $jobs = DB::table('jobs')->whereNotNull('originalFile')->get();
        
        foreach ($jobs as $job) {
            if (!empty($job->originalFile)) {
                $fileArray = json_decode($job->originalFile, true);
                // Take the first file if it's an array, otherwise use as is
                $firstFile = is_array($fileArray) && !empty($fileArray) ? $fileArray[0] : $job->originalFile;
                
                DB::table('jobs')
                    ->where('id', $job->id)
                    ->update(['originalFile' => $firstFile]);
            }
        }

        // Change the column type back to string
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('originalFile', 255)->nullable()->change();
        });
    }
};
