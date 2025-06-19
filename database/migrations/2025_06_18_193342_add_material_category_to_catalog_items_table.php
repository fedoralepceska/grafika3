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
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->unsignedBigInteger('large_material_category_id')->nullable()->after('large_material_id');
            $table->unsignedBigInteger('small_material_category_id')->nullable()->after('small_material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('large_material_category_id');
            $table->dropColumn('small_material_category_id');
        });
    }
};
