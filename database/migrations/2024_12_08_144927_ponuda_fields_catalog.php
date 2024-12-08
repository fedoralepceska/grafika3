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
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->boolean('is_for_offer')->nullable();
            $table->boolean('is_for_sales')->nullable();
            $table->enum('category', ['material', 'article', 'small_format'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('is_for_offer');
            $table->dropColumn('is_for_sales');
            $table->dropColumn('category');
        });
    }
};
