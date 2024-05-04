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
        Schema::create('article', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->integer('tax_type')->nullable(); // DDV A,B
            $table->string('barcode')->nullable();
            $table->string('comment')->nullable();
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->float('length')->nullable();
            $table->float('weight')->nullable();
            $table->string('color')->nullable();
            $table->float('purchase_price')->nullable();
            $table->float('factory_price')->nullable();
            $table->float('price_1')->nullable();
            $table->boolean('in_meters')->nullable();
            $table->boolean('in_kilograms')->nullable();
            $table->boolean('in_pieces')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article');
    }
};
