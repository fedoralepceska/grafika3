<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_item_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->onDelete('cascade');
            $table->foreignId('article_id')->constrained('article')->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->timestamps();
        });

        // Add cost_price column to catalog_items table
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_item_articles');

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
