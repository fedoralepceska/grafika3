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
        // Add indexes one by one with error handling
        
        // Index for active column
        try {
            Schema::table('questions', function (Blueprint $table) {
                $table->index('active', 'questions_active_idx');
            });
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        // Index for order column
        try {
            Schema::table('questions', function (Blueprint $table) {
                $table->index('order', 'questions_order_idx');
            });
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        // Composite index for active + order (most important for performance)
        try {
            Schema::table('questions', function (Blueprint $table) {
                $table->index(['active', 'order'], 'questions_active_order_idx');
            });
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        // Add indexes to the pivot table for better join performance
        try {
            Schema::table('catalog_item_questions', function (Blueprint $table) {
                $table->index('catalog_item_id', 'ciq_catalog_item_idx');
            });
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        try {
            Schema::table('catalog_item_questions', function (Blueprint $table) {
                $table->index('question_id', 'ciq_question_idx');
            });
        } catch (\Exception $e) {
            // Index might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes with proper names and error handling
        try {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropIndex('questions_active_idx');
            });
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        try {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropIndex('questions_order_idx');
            });
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        try {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropIndex('questions_active_order_idx');
            });
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        try {
            Schema::table('catalog_item_questions', function (Blueprint $table) {
                $table->dropIndex('ciq_catalog_item_idx');
            });
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        try {
            Schema::table('catalog_item_questions', function (Blueprint $table) {
                $table->dropIndex('ciq_question_idx');
            });
        } catch (\Exception $e) {
            // Index might not exist
        }
    }
};
