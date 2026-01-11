<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfills fiscal_year for existing stock_realizations records.
     */
    public function up(): void
    {
        // Get all stock_realizations records without fiscal_year
        $stockRealizations = DB::table('stock_realizations')
            ->whereNull('fiscal_year')
            ->get();

        foreach ($stockRealizations as $sr) {
            // Use created_at to determine fiscal year
            $createdAt = $sr->created_at;
            $fiscalYear = $createdAt ? (int) date('Y', strtotime($createdAt)) : (int) date('Y');

            DB::table('stock_realizations')
                ->where('id', $sr->id)
                ->update([
                    'fiscal_year' => $fiscalYear,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('stock_realizations')->update([
            'fiscal_year' => null,
        ]);
    }
};
