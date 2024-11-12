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
        Schema::table('small_material', function (Blueprint $table) {
            $table->string('width')->nullable()->change();
            $table->string('height')->nullable()->change();
            $table->string('quantity')->nullable()->change();
            $table->string('price_per_unit')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('small_material', function (Blueprint $table) {
            //
        });
    }
};
