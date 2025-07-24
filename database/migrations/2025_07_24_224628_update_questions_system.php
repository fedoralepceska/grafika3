<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Remove default_answer column from questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('default_answer');
        });
        
        // Create pivot table for catalog items and questions
        Schema::create('catalog_item_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['catalog_item_id', 'question_id']);
        });
    }

    public function down()
    {
        // Drop the pivot table
        Schema::dropIfExists('catalog_item_questions');
        
        // Add back default_answer column to questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->string('default_answer')->after('question');
        });
    }
};
