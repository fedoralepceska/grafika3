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
        Schema::table('article', function (Blueprint $table) {
            $table->string('tax_type')->nullable()->change();
            $table->string('barcode')->nullable()->change();
            $table->string('comment')->nullable()->change();
            $table->string('width')->nullable()->change();
            $table->string('height')->nullable()->change();
            $table->string('length')->nullable()->change();
            $table->string('weight')->nullable()->change();
            $table->string('color')->nullable()->change();
            $table->string('purchase_price')->nullable()->change();
            $table->string('factory_price')->nullable()->change();
            $table->string('price_1')->nullable()->change();
            $table->string('in_kilograms')->nullable()->change();
            $table->string('in_meters')->nullable()->change();
            $table->string('in_pieces')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article', function (Blueprint $table) {
            //
        });
    }
};
