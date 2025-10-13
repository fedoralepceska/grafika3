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
        Schema::create('additional_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faktura_id');
            $table->string('name');
            $table->decimal('quantity', 10, 4);
            $table->string('unit', 10)->default('ком');
            $table->decimal('sale_price', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(18.00);
            $table->timestamps();

            $table->foreign('faktura_id')->references('id')->on('faktura')->onDelete('cascade');
            $table->index('faktura_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_services');
    }
};
