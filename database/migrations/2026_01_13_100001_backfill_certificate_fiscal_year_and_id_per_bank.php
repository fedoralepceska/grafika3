<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfills fiscal_year and recalculates id_per_bank for existing certificate records.
     */
    public function up(): void
    {
        // Get all certificates grouped by bank and year
        $certificates = DB::table('certificate')
            ->whereNull('fiscal_year')
            ->orderBy('bank')
            ->orderBy('created_at', 'asc')
            ->get();

        // Group by bank and fiscal year
        $grouped = [];
        foreach ($certificates as $certificate) {
            $createdAt = $certificate->created_at;
            $fiscalYear = $createdAt ? (int) date('Y', strtotime($createdAt)) : (int) date('Y');
            $bank = $certificate->bank;

            $key = "{$bank}_{$fiscalYear}";
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'bank' => $bank,
                    'fiscal_year' => $fiscalYear,
                    'certificates' => [],
                ];
            }
            $grouped[$key]['certificates'][] = $certificate;
        }

        // Process each group and assign sequential id_per_bank
        foreach ($grouped as $group) {
            $bank = $group['bank'];
            $fiscalYear = $group['fiscal_year'];

            // Get the max id_per_bank for this bank and fiscal year (in case some already exist)
            $maxIdPerBank = DB::table('certificate')
                ->where('bank', $bank)
                ->where('fiscal_year', $fiscalYear)
                ->max('id_per_bank') ?? 0;

            $seq = $maxIdPerBank;

            foreach ($group['certificates'] as $certificate) {
                $seq++;
                DB::table('certificate')
                    ->where('id', $certificate->id)
                    ->update([
                        'fiscal_year' => $fiscalYear,
                        'id_per_bank' => $seq,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset fiscal_year to null (id_per_bank will retain its value)
        DB::table('certificate')->update([
            'fiscal_year' => null,
        ]);
    }
};
