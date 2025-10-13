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
        Schema::table('faktura', function (Blueprint $table) {
            $table->json('faktura_overrides')->nullable()->after('split_group_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faktura', function (Blueprint $table) {
            $table->dropColumn('faktura_overrides');
        });
    }
};
