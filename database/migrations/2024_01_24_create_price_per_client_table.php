<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('price_per_client', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            // Ensure unique combination of catalog_item and client
            $table->unique(['catalog_item_id', 'client_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('price_per_client');
    }
}; 