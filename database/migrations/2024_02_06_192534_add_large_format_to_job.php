<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('materials');

            // Add a new column to store the ID of the MaterialSmall
            $table->unsignedBigInteger('large_material_id')->nullable();
            $table->foreign('large_material_id')->references('id')->on('large_format_materials');
        });
    }

    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['large_material_id']);
            $table->dropColumn('large_material_id');
        });
    }
};
