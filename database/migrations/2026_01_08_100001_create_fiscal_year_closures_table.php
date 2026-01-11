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
        Schema::create('fiscal_year_closures', function (Blueprint $table) {
            $table->id();
            $table->integer('fiscal_year');
            $table->string('module', 50);
            $table->timestamp('closed_at');
            $table->foreignId('closed_by')->constrained('users');
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year', 'module']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_year_closures');
    }
};
