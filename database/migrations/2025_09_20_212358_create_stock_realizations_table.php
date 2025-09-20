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
        Schema::create('stock_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->string('invoice_title');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('comment')->nullable();
            $table->boolean('is_realized')->default(false);
            $table->timestamp('realized_at')->nullable();
            $table->foreignId('realized_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('stock_realization_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_realization_id')->constrained('stock_realizations')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->string('name');
            $table->integer('quantity');
            $table->integer('copies');
            $table->decimal('total_area_m2', 10, 4)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->json('dimensions_breakdown')->nullable();
            $table->foreignId('small_material_id')->nullable()->constrained('small_material')->onDelete('set null');
            $table->foreignId('large_material_id')->nullable()->constrained('large_format_materials')->onDelete('set null');
            $table->foreignId('catalog_item_id')->nullable()->constrained('catalog_items')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('stock_realization_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_realization_job_id')->constrained('stock_realization_jobs')->onDelete('cascade');
            $table->foreignId('article_id')->constrained('article')->onDelete('cascade');
            $table->decimal('quantity', 10, 4);
            $table->string('unit_type')->default('pieces'); // pieces, square_meters, etc.
            $table->string('source')->default('catalog_item'); // catalog_item_recomputed, job_direct, legacy
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_realization_articles');
        Schema::dropIfExists('stock_realization_jobs');
        Schema::dropIfExists('stock_realizations');
    }
};
