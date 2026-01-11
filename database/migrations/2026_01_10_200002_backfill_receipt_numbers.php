<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfills receipt_number and fiscal_year for existing priemnica records.
     */
    public function up(): void
    {
        // Get all priemnica records ordered by created_at, grouped by year
        $priemnicas = DB::table('priemnica')
            ->whereNull('receipt_number')
            ->orderBy('created_at', 'asc')
            ->get();

        $yearCounters = [];

        foreach ($priemnicas as $priemnica) {
            $createdAt = $priemnica->created_at;
            $fiscalYear = $createdAt ? (int) date('Y', strtotime($createdAt)) : (int) date('Y');

            if (!isset($yearCounters[$fiscalYear])) {
                // Get the max receipt_number for this fiscal year (in case some already exist)
                $maxNumber = DB::table('priemnica')
                    ->where('fiscal_year', $fiscalYear)
                    ->max('receipt_number') ?? 0;
                $yearCounters[$fiscalYear] = $maxNumber;
            }

            $yearCounters[$fiscalYear]++;

            DB::table('priemnica')
                ->where('id', $priemnica->id)
                ->update([
                    'receipt_number' => $yearCounters[$fiscalYear],
                    'fiscal_year' => $fiscalYear,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('priemnica')->update([
            'receipt_number' => null,
            'fiscal_year' => null,
        ]);
    }
};
