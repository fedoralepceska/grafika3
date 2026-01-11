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
        Schema::table('priemnica', function (Blueprint $table) {
            $table->unsignedInteger('receipt_number')->nullable()->after('id');
            $table->unsignedSmallInteger('fiscal_year')->nullable()->after('receipt_number');
            
            $table->index(['fiscal_year', 'receipt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('priemnica', function (Blueprint $table) {
            $table->dropIndex(['fiscal_year', 'receipt_number']);
            $table->dropColumn(['receipt_number', 'fiscal_year']);
        });
    }
};
