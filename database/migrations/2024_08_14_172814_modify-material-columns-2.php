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
        Schema::table('dorabotkaable', function (Blueprint $table) {
            $table->unsignedBigInteger('material_id')->nullable()->change();
            $table->string('material_type')->nullable()->change();
            $table->id();
            $table->foreign('dorabotka_id')->references('id')->on('dorabotka')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dorabotkaable', function (Blueprint $table) {
            //
        });
    }
};
