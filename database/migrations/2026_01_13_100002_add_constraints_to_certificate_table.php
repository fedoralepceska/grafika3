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
        Schema::table('certificate', function (Blueprint $table) {
            // Make fiscal_year NOT NULL
            $table->unsignedSmallInteger('fiscal_year')->nullable(false)->change();

            // Add unique constraint on id_per_bank, fiscal_year, and bank
            $table->unique(['id_per_bank', 'fiscal_year', 'bank'], 'idx_certificate_bank_fiscal_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate', function (Blueprint $table) {
            $table->dropUnique('idx_certificate_bank_fiscal_number');
            $table->unsignedSmallInteger('fiscal_year')->nullable()->change();
        });
    }
};
