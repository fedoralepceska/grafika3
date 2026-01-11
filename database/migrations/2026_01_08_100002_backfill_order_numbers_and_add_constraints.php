<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill 2025 orders: order_number = id, fiscal_year = 2025
        DB::statement("
            UPDATE invoices 
            SET order_number = id, fiscal_year = 2025 
            WHERE YEAR(created_at) = 2025
        ");

        // Backfill 2026 orders with sequential numbering starting from 1
        $orders2026 = DB::table('invoices')
            ->whereYear('created_at', 2026)
            ->orderBy('id')
            ->get();

        $seq = 1;
        foreach ($orders2026 as $order) {
            DB::table('invoices')
                ->where('id', $order->id)
                ->update([
                    'order_number' => $seq++,
                    'fiscal_year' => 2026,
                ]);
        }

        // Handle any orders from other years (before 2025)
        DB::statement("
            UPDATE invoices 
            SET order_number = id, fiscal_year = YEAR(created_at) 
            WHERE fiscal_year IS NULL
        ");

        // Now add NOT NULL constraints and unique index
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('order_number')->nullable(false)->change();
            $table->integer('fiscal_year')->nullable(false)->change();
            $table->unique(['order_number', 'fiscal_year'], 'idx_order_fiscal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('idx_order_fiscal');
            $table->integer('order_number')->nullable()->change();
            $table->integer('fiscal_year')->nullable()->change();
        });
    }
};
