<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateMigrationReadiness extends Command
{
    protected $signature = 'migration:validate-year-end-census';
    protected $description = 'Validate data before running year-end census migrations';

    public function handle()
    {
        $this->info('Validating migration readiness for Year-End Census...');
        $this->newLine();

        // Check invoices table exists
        if (!DB::getSchemaBuilder()->hasTable('invoices')) {
            $this->error('❌ invoices table does not exist!');
            return 1;
        }
        $this->info('✓ invoices table exists');

        // Check if columns already exist
        $existingColumns = DB::getSchemaBuilder()->getColumnListing('invoices');
        $newColumns = ['order_number', 'fiscal_year', 'archived', 'archived_at'];
        
        foreach ($newColumns as $col) {
            if (in_array($col, $existingColumns)) {
                $this->warn("⚠ Column '{$col}' already exists - migration may fail");
            } else {
                $this->info("✓ Column '{$col}' does not exist (will be created)");
            }
        }

        // Count existing invoices by year
        $this->newLine();
        $this->info('Invoice counts by year (based on created_at):');
        
        $yearCounts = DB::table('invoices')
            ->selectRaw('YEAR(created_at) as year, COUNT(*) as count')
            ->groupBy(DB::raw('YEAR(created_at)'))
            ->orderBy('year')
            ->get();

        if ($yearCounts->isEmpty()) {
            $this->warn('No invoices found in database');
        } else {
            foreach ($yearCounts as $row) {
                $this->line("  {$row->year}: {$row->count} invoices");
            }
        }

        // Check for null created_at values
        $nullCreatedAt = DB::table('invoices')->whereNull('created_at')->count();
        if ($nullCreatedAt > 0) {
            $this->error("❌ {$nullCreatedAt} invoices have NULL created_at - these need to be fixed first!");
            return 1;
        }
        $this->info('✓ All invoices have created_at values');

        // Check users table for foreign key
        if (!DB::getSchemaBuilder()->hasTable('users')) {
            $this->error('❌ users table does not exist (needed for fiscal_year_closures)!');
            return 1;
        }
        $this->info('✓ users table exists');

        $this->newLine();
        $this->info('✅ Validation complete. Safe to run: php artisan migrate');
        
        return 0;
    }
}
