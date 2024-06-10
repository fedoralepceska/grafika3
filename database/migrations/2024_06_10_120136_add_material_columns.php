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
            $table->string('material_type')->nullable();
            $table->unsignedBigInteger('material_id')->nullable();
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
