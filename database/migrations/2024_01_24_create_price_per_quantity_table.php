<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('price_per_quantity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->integer('quantity_from')->nullable();
            $table->integer('quantity_to')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        // Add check constraints using raw SQL
        DB::statement('ALTER TABLE price_per_quantity ADD CONSTRAINT check_quantity_boundaries CHECK (quantity_from IS NOT NULL OR quantity_to IS NOT NULL)');
        DB::statement('ALTER TABLE price_per_quantity ADD CONSTRAINT check_quantity_range CHECK (quantity_to IS NULL OR quantity_from IS NULL OR quantity_from < quantity_to)');
    }

    public function down()
    {
        Schema::dropIfExists('price_per_quantity');
    }
}; 