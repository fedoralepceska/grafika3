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
        Schema::table('priemnica', function (Blueprint $table) {
            $table->unsignedBigInteger('article_id')->nullable();
            $table->foreign('article_id')->references('id')->on('article')->onDelete('cascade');
        });
        Schema::table('priemnica', function (Blueprint $table) {
            $table->dropForeign(['article_id']);
            $table->dropColumn('article_id');
        });
        Schema::create('priemnica_articles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('priemnica_id');
                $table->unsignedBigInteger('article_id');
                $table->foreign('priemnica_id')->references('id')->on('priemnica')->onDelete('cascade');
                $table->foreign('article_id')->references('id')->on('article')->onDelete('cascade');
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('priemnica_articles');
    }
};
