<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateFakturaMigrationReadiness extends Command
{
    protected $signature = 'migration:validate-faktura-numbers';
    protected $description = 'Validate data before running faktura number migrations';

    public function handle()
    {
        $this->info('Validating migration readiness for Faktura Numbers...');
        $this->newLine();

        // Check faktura table exists
        if (!DB::getSchemaBuilder()->hasTable('faktura')) {
            $this->error('❌ faktura table does not exist!');
            return 1;
        }
        $this->info('✓ faktura table exists');

        // Check if columns already exist
        $existingColumns = DB::getSchemaBuilder()->getColumnListing('faktura');
        $newColumns = ['faktura_number', 'fiscal_year'];
        
        foreach ($newColumns as $col) {
            if (in_array($col, $existingColumns)) {
                $this->warn("⚠ Column '{$col}' already exists - migration may skip or fail");
            } else {
                $this->info("✓ Column '{$col}' does not exist (will be created)");
            }
        }

        // Count existing fakturas by year
        $this->newLine();
        $this->info('Faktura counts by year (based on created_at):');
        
        $yearCounts = DB::table('faktura')
            ->selectRaw('YEAR(created_at) as year, COUNT(*) as count')
            ->groupBy(DB::raw('YEAR(created_at)'))
            ->orderBy('year')
            ->get();

        if ($yearCounts->isEmpty()) {
            $this->warn('No fakturas found in database');
        } else {
            foreach ($yearCounts as $row) {
                $this->line("  {$row->year}: {$row->count} fakturas");
            }
        }

        // Check for null created_at values
        $nullCreatedAt = DB::table('faktura')->whereNull('created_at')->count();
        if ($nullCreatedAt > 0) {
            $this->error("❌ {$nullCreatedAt} fakturas have NULL created_at - these need to be fixed first!");
            return 1;
        }
        $this->info('✓ All fakturas have created_at values');

        // Show total count
        $totalFakturas = DB::table('faktura')->count();
        $this->newLine();
        $this->info("Total fakturas to be numbered: {$totalFakturas}");

        // Preview what the numbering would look like
        $this->newLine();
        $this->info('Preview of faktura numbering (first 5 per year):');
        
        foreach ($yearCounts as $row) {
            if ($row->year) {
                $this->line("  Year {$row->year}:");
                $preview = DB::table('faktura')
                    ->whereYear('created_at', $row->year)
                    ->orderBy('id')
                    ->limit(5)
                    ->get(['id', 'created_at']);
                
                $seq = 1;
                foreach ($preview as $f) {
                    $this->line("    ID {$f->id} → faktura_number: {$seq}, fiscal_year: {$row->year}");
                    $seq++;
                }
                if ($row->count > 5) {
                    $this->line("    ... and " . ($row->count - 5) . " more");
                }
            }
        }

        $this->newLine();
        $this->info('✅ Validation complete. Safe to run: php artisan migrate');
        
        return 0;
    }
}
