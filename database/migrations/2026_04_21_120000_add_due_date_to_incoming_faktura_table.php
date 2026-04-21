<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * due_date — regional accounting "datum valute" / value date (often payment due date).
 * Legacy UIs often label this "Валута" but bind a date picker; it is not ISO currency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_faktura', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('incoming_faktura', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
