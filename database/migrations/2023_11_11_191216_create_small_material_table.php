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
        Schema::create('small_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('small_format_material_id')->constrained(); // This creates the foreign key
            $table->string('name');
            $table->double('width');
            $table->double('height');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('small_material');
    }
};
