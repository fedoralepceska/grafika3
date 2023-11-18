<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
    /**
     * Run the migrations.
     */
{
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('materialsSmall');

            // Add a new column to store the ID of the MaterialSmall
            $table->unsignedBigInteger('small_material_id')->nullable();
            $table->foreign('small_material_id')->references('id')->on('small_material');
        });
    }

    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['small_material_id']);
            $table->dropColumn('small_material_id');
        });
    }
};
