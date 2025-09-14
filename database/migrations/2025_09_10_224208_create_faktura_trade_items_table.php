<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('faktura_trade_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faktura_id')->constrained('faktura')->onDelete('cascade');
            $table->foreignId('article_id')->constrained('article')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(18.00);
            $table->decimal('vat_amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('faktura_trade_items');
    }
};