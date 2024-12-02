<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // First drop the existing table
        Schema::dropIfExists('catalog_items');

        // Then create the new table with correct structure
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('machinePrint')->nullable();
            $table->string('machineCut')->nullable();
            $table->foreignId('large_material_id')->nullable()->constrained('large_format_materials');
            $table->foreignId('small_material_id')->nullable()->constrained('small_material');
            $table->integer('quantity')->default(1);
            $table->integer('copies')->default(1);
            $table->json('actions')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('catalog_items');
    }
};
