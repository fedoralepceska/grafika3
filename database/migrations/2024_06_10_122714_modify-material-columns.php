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
        Schema::table('dorabotka', function (Blueprint $table) {
            $table->unsignedBigInteger('small_material_id')->nullable();
            $table->foreign('small_material_id')->references('id')->on('small_material')->onDelete('cascade');
            $table->unsignedBigInteger('large_material_id')->nullable();
            $table->foreign('large_material_id')->references('id')->on('large_format_materials')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dorabotka', function (Blueprint $table) {
            //
        });
    }
};
