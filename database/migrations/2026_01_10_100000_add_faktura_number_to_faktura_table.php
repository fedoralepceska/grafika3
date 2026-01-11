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
            $table->integer('faktura_number')->nullable()->after('id');
            $table->integer('fiscal_year')->nullable()->after('faktura_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faktura', function (Blueprint $table) {
            $table->dropColumn(['faktura_number', 'fiscal_year']);
        });
    }
};
