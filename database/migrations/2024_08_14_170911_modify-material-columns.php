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
            $table->dropForeign(['dorabotka_id']);
            $table->dropPrimary(['dorabotka_id', 'material_type']);
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
