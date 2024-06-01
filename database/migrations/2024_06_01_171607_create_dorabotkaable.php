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
        Schema::create('dorabotkaable', function (Blueprint $table) {
            $table->unsignedBigInteger('dorabotka_id');
            $table->string('material_type');
            $table->unsignedBigInteger('material_id');

            $table->primary(['dorabotka_id', 'material_type']); // Composite primary key

            $table->foreign('dorabotka_id')->references('id')->on('dorabotka')->onDelete('cascade');
            // No foreign key for material_id as it can reference either small_material or large_material
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dorabotkaable');
    }
};
