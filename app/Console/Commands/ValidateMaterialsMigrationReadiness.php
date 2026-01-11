<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateMaterialsMigrationReadiness extends Command
{
    protected $signature = 'migrate:validate-materials {--dry-run : Show what would be migrated without making changes}';
    protected $description = 'Validate and preview the materials (priemnica & stock_realizations) migration for fiscal year numbering';

    public function handle()
    {
        $this->info('=== Materials Migration Validation ===');
        $this->newLine();

        $isDryRun = $this->option('dry-run');

        // Check Priemnica
        $this->validatePriemnica($isDryRun);
        $this->newLine();

        // Check Stock Realizations
        $this->validateStockRealizations($isDryRun);
        $this->newLine();

        if ($isDryRun) {
            $this->warn('This was a DRY RUN. No changes were made.');
            $this->info('Run without --dry-run to apply the migrations.');
        }

        return Command::SUCCESS;
    }

    private function validatePriemnica(bool $isDryRun): void
    {
        $this->info('--- Priemnica (Receipts) ---');

        // Check if columns exist
        $hasReceiptNumber = Schema::hasColumn('priemnica', 'receipt_number');
        $hasFiscalYear = Schema::hasColumn('priemnica', 'fiscal_year');

        $this->line("receipt_number column exists: " . ($hasReceiptNumber ? 'Yes' : 'No'));
        $this->line("fiscal_year column exists: " . ($hasFiscalYear ? 'Yes' : 'No'));

        // Count records
        $totalCount = DB::table('priemnica')->count();
        $this->line("Total priemnica records: {$totalCount}");

        if ($hasReceiptNumber && $hasFiscalYear) {
            $withNumbers = DB::table('priemnica')
                ->whereNotNull('receipt_number')
                ->whereNotNull('fiscal_year')
                ->count();
            $withoutNumbers = $totalCount - $withNumbers;

            $this->line("Records with receipt_number: {$withNumbers}");
            $this->line("Records needing backfill: {$withoutNumbers}");
        }

        // Preview what would be assigned
        if ($isDryRun && $totalCount > 0) {
            $this->newLine();
            $this->info('Preview of receipt number assignments:');

            $priemnicas = DB::table('priemnica')
                ->orderBy('created_at', 'asc')
                ->limit(20)
                ->get();

            $yearCounters = [];
            $previewData = [];

            foreach ($priemnicas as $priemnica) {
                $createdAt = $priemnica->created_at;
                $fiscalYear = $createdAt ? (int) date('Y', strtotime($createdAt)) : (int) date('Y');

                if (!isset($yearCounters[$fiscalYear])) {
                    $yearCounters[$fiscalYear] = 0;
                }
                $yearCounters[$fiscalYear]++;

                $previewData[] = [
                    'ID' => $priemnica->id,
                    'Created' => $createdAt ? date('Y-m-d', strtotime($createdAt)) : 'N/A',
                    'Would Assign' => "{$yearCounters[$fiscalYear]}/{$fiscalYear}",
                ];
            }

            $this->table(['ID', 'Created', 'Would Assign'], $previewData);

            if ($totalCount > 20) {
                $this->line("... and " . ($totalCount - 20) . " more records");
            }

            // Summary by year
            $this->newLine();
            $this->info('Records by fiscal year:');
            $byYear = DB::table('priemnica')
                ->selectRaw('YEAR(created_at) as year, COUNT(*) as count')
                ->groupBy(DB::raw('YEAR(created_at)'))
                ->orderBy('year')
                ->get();

            foreach ($byYear as $row) {
                $this->line("  {$row->year}: {$row->count} receipts");
            }
        }
    }

    private function validateStockRealizations(bool $isDryRun): void
    {
        $this->info('--- Stock Realizations ---');

        // Check if column exists
        $hasFiscalYear = Schema::hasColumn('stock_realizations', 'fiscal_year');

        $this->line("fiscal_year column exists: " . ($hasFiscalYear ? 'Yes' : 'No'));

        // Count records
        $totalCount = DB::table('stock_realizations')->count();
        $realizedCount = DB::table('stock_realizations')->where('is_realized', true)->count();

        $this->line("Total stock realizations: {$totalCount}");
        $this->line("Realized (completed): {$realizedCount}");

        if ($hasFiscalYear) {
            $withFiscalYear = DB::table('stock_realizations')
                ->whereNotNull('fiscal_year')
                ->count();
            $withoutFiscalYear = $totalCount - $withFiscalYear;

            $this->line("Records with fiscal_year: {$withFiscalYear}");
            $this->line("Records needing backfill: {$withoutFiscalYear}");
        }

        // Preview
        if ($isDryRun && $totalCount > 0) {
            $this->newLine();
            $this->info('Preview of fiscal year assignments:');

            $stockRealizations = DB::table('stock_realizations')
                ->orderBy('created_at', 'asc')
                ->limit(20)
                ->get();

            $previewData = [];

            foreach ($stockRealizations as $sr) {
                $createdAt = $sr->created_at;
                $fiscalYear = $createdAt ? (int) date('Y', strtotime($createdAt)) : (int) date('Y');

                $previewData[] = [
                    'ID' => $sr->id,
                    'Created' => $createdAt ? date('Y-m-d', strtotime($createdAt)) : 'N/A',
                    'Realized' => $sr->is_realized ? 'Yes' : 'No',
                    'Would Assign' => $fiscalYear,
                ];
            }

            $this->table(['ID', 'Created', 'Realized', 'Would Assign'], $previewData);

            if ($totalCount > 20) {
                $this->line("... and " . ($totalCount - 20) . " more records");
            }

            // Summary by year
            $this->newLine();
            $this->info('Records by fiscal year:');
            $byYear = DB::table('stock_realizations')
                ->selectRaw('YEAR(created_at) as year, COUNT(*) as count, SUM(CASE WHEN is_realized = 1 THEN 1 ELSE 0 END) as realized')
                ->groupBy(DB::raw('YEAR(created_at)'))
                ->orderBy('year')
                ->get();

            foreach ($byYear as $row) {
                $this->line("  {$row->year}: {$row->count} total, {$row->realized} realized");
            }
        }
    }
}
