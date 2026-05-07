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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('validity_days')->default(30);
            $table->date('production_start_date');
            $table->date('production_end_date');
            $table->decimal('price1', 10, 2)->nullable();
            $table->decimal('price2', 10, 2)->nullable();
            $table->decimal('price3', 10, 2)->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->text('decline_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('catalog_item_offer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_item_offer');
        Schema::dropIfExists('offers');
    }
};
