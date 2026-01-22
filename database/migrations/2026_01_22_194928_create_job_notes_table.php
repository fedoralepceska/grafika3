<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->text('comment');
            $table->json('selected_actions')->nullable(); // Store which actions should show this note
            $table->timestamps();
            
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->index('job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_notes');
    }
};
