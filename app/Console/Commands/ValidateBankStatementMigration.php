<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateBankStatementMigration extends Command
{
    protected $signature = 'migration:validate-bank-statements';
    protected $description = 'Validate data before running bank statement fiscal year migrations';

    public function handle()
    {
        $this->info('Validating migration readiness for Bank Statement Fiscal Year...');
        $this->newLine();

        // Check certificate table exists
        if (!DB::getSchemaBuilder()->hasTable('certificate')) {
            $this->error('❌ certificate table does not exist!');
            return 1;
        }
        $this->info('✓ certificate table exists');

        // Check if columns already exist
        $existingColumns = DB::getSchemaBuilder()->getColumnListing('certificate');
        $newColumns = ['fiscal_year', 'archived', 'archived_at'];
        
        foreach ($newColumns as $col) {
            if (in_array($col, $existingColumns)) {
                $this->warn("⚠ Column '{$col}' already exists - migration may fail");
            } else {
                $this->info("✓ Column '{$col}' does not exist (will be created)");
            }
        }

        // Check if id_per_bank column exists
        if (!in_array('id_per_bank', $existingColumns)) {
            $this->error('❌ id_per_bank column does not exist - this is required!');
            return 1;
        }
        $this->info('✓ id_per_bank column exists');

        // Count existing certificates by year
        $this->newLine();
        $this->info('Certificate counts by year (based on created_at):');
        
        $yearCounts = DB::table('certificate')
            ->selectRaw('YEAR(created_at) as year, COUNT(*) as count')
            ->groupBy(DB::raw('YEAR(created_at)'))
            ->orderBy('year')
            ->get();

        if ($yearCounts->isEmpty()) {
            $this->warn('No certificates found in database');
        } else {
            foreach ($yearCounts as $row) {
                $this->line("  {$row->year}: {$row->count} certificates");
            }
        }

        // Count by bank and year
        $this->newLine();
        $this->info('Certificate counts by bank and year:');
        
        $bankYearCounts = DB::table('certificate')
            ->selectRaw('bank, YEAR(created_at) as year, COUNT(*) as count')
            ->groupBy('bank', DB::raw('YEAR(created_at)'))
            ->orderBy('bank')
            ->orderBy('year')
            ->get();

        if ($bankYearCounts->isEmpty()) {
            $this->warn('No certificates found in database');
        } else {
            foreach ($bankYearCounts as $row) {
                $this->line("  {$row->bank} ({$row->year}): {$row->count} certificates");
            }
        }

        // Check for null created_at values
        $nullCreatedAt = DB::table('certificate')->whereNull('created_at')->count();
        if ($nullCreatedAt > 0) {
            $this->warn("⚠ {$nullCreatedAt} certificates have NULL created_at - these will use current year");
        } else {
            $this->info('✓ All certificates have created_at values');
        }

        // Check for null bank values
        $nullBank = DB::table('certificate')->whereNull('bank')->count();
        if ($nullBank > 0) {
            $this->error("❌ {$nullBank} certificates have NULL bank - these need to be fixed first!");
            return 1;
        }
        $this->info('✓ All certificates have bank values');

        // Check for potential duplicate id_per_bank within same bank and year
        $this->newLine();
        $this->info('Checking for potential id_per_bank conflicts after migration...');
        
        $duplicates = DB::table('certificate')
            ->selectRaw('bank, YEAR(created_at) as year, id_per_bank, COUNT(*) as count')
            ->groupBy('bank', DB::raw('YEAR(created_at)'), 'id_per_bank')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->warn('⚠ Found duplicate id_per_bank values that will be recalculated:');
            foreach ($duplicates as $dup) {
                $this->line("  {$dup->bank} ({$dup->year}): id_per_bank {$dup->id_per_bank} appears {$dup->count} times");
            }
        } else {
            $this->info('✓ No duplicate id_per_bank conflicts detected');
        }

        // Check users table for foreign key (for fiscal_year_closures)
        if (!DB::getSchemaBuilder()->hasTable('users')) {
            $this->error('❌ users table does not exist (needed for fiscal_year_closures)!');
            return 1;
        }
        $this->info('✓ users table exists');

        // Check fiscal_year_closures table exists
        if (!DB::getSchemaBuilder()->hasTable('fiscal_year_closures')) {
            $this->warn('⚠ fiscal_year_closures table does not exist - year-end closure features will not work until it is created');
        } else {
            $this->info('✓ fiscal_year_closures table exists');
        }

        $this->newLine();
        $this->info('✅ Validation complete. Safe to run: php artisan migrate');
        
        return 0;
    }
}
