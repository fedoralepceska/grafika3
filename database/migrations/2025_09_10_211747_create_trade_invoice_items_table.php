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
        Schema::create('trade_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trade_invoice_id');
            $table->foreign('trade_invoice_id')->references('id')->on('trade_invoices')->onDelete('cascade');
            $table->unsignedBigInteger('article_id');
            $table->foreign('article_id')->references('id')->on('article')->onDelete('cascade');
            $table->decimal('quantity', 15, 5);
            $table->decimal('unit_price', 10, 2);
            $table->integer('tax_type'); // 1=18%, 2=5%, 3=10%, 0=0%
            $table->decimal('line_total', 12, 2);
            $table->decimal('vat_amount', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_invoice_items');
    }
};
