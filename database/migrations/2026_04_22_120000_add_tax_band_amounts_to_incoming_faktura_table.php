<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('incoming_faktura', function (Blueprint $table) {
            $table->float('tax_a_amount')->nullable()->after('tax');
            $table->float('tax_b_amount')->nullable()->after('tax_a_amount');
            $table->float('tax_c_amount')->nullable()->after('tax_b_amount');
            $table->float('tax_d_amount')->nullable()->after('tax_c_amount');
        });

        // Backfill existing production rows:
        // move legacy aggregated amount into A, keep other bands zero.
        DB::table('incoming_faktura')->update([
            'tax_a_amount' => DB::raw('COALESCE(amount, 0)'),
            'tax_b_amount' => 0,
            'tax_c_amount' => 0,
            'tax_d_amount' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_faktura', function (Blueprint $table) {
            $table->dropColumn([
                'tax_a_amount',
                'tax_b_amount',
                'tax_c_amount',
                'tax_d_amount',
            ]);
        });
    }
};
