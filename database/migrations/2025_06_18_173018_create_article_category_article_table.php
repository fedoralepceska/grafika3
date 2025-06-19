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
        Schema::create('article_category_article', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_category_id');
            $table->unsignedBigInteger('article_id');
            $table->foreign('article_category_id')->references('id')->on('article_categories')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('article')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_category_article');
    }
};
