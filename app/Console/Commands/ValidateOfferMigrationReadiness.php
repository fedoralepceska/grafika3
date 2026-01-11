<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateOfferMigrationReadiness extends Command
{
    protected $signature = 'migrate:validate-offers {--dry-run : Show what would happen without making changes}';
    protected $description = 'Validate and preview the offer number migration';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('=== Offer Number Migration Validation ===');
        $this->newLine();

        // Check if columns already exist
        $hasOfferNumber = Schema::hasColumn('offers', 'offer_number');
        $hasFiscalYear = Schema::hasColumn('offers', 'fiscal_year');

        if ($hasOfferNumber && $hasFiscalYear) {
            $this->warn('Columns offer_number and fiscal_year already exist in offers table.');
            
            // Show current state
            $offers = DB::table('offers')
                ->select('id', 'offer_number', 'fiscal_year', 'created_at')
                ->orderBy('fiscal_year', 'desc')
                ->orderBy('offer_number', 'desc')
                ->limit(20)
                ->get();

            $this->info('Current offers (last 20):');
            $this->table(
                ['ID', 'Offer Number', 'Fiscal Year', 'Created At'],
                $offers->map(fn($o) => [$o->id, $o->offer_number, $o->fiscal_year, $o->created_at])
            );

            return 0;
        }

        // Get total counts
        $totalOffers = DB::table('offers')->count();
        $this->info("Total offers in database: {$totalOffers}");
        $this->newLine();

        // Get offers by year
        $offersByYear = DB::table('offers')
            ->selectRaw('YEAR(created_at) as year, COUNT(*) as count, MIN(id) as min_id, MAX(id) as max_id')
            ->groupBy(DB::raw('YEAR(created_at)'))
            ->orderBy('year')
            ->get();

        $this->info('Offers by year:');
        $this->table(
            ['Year', 'Count', 'Min ID', 'Max ID'],
            $offersByYear->map(fn($r) => [$r->year, $r->count, $r->min_id, $r->max_id])
        );
        $this->newLine();

        // Preview what would happen
        $this->info('=== Migration Preview ===');
        $this->newLine();

        // 2025 offers
        $offers2025 = DB::table('offers')
            ->whereYear('created_at', 2025)
            ->orderBy('id')
            ->get(['id', 'created_at']);

        if ($offers2025->count() > 0) {
            $this->info("2025 Offers ({$offers2025->count()} total):");
            $this->info("  - Will set offer_number = id, fiscal_year = 2025");
            
            $preview2025 = $offers2025->take(5)->map(fn($o) => [
                $o->id,
                $o->id, // offer_number = id
                2025,
                $o->created_at
            ]);
            
            $this->table(
                ['ID', 'New Offer Number', 'Fiscal Year', 'Created At'],
                $preview2025
            );
            
            if ($offers2025->count() > 5) {
                $this->info("  ... and " . ($offers2025->count() - 5) . " more");
            }
            $this->newLine();
        }

        // 2026 offers
        $offers2026 = DB::table('offers')
            ->whereYear('created_at', 2026)
            ->orderBy('id')
            ->get(['id', 'created_at']);

        if ($offers2026->count() > 0) {
            $this->info("2026 Offers ({$offers2026->count()} total):");
            $this->info("  - Will set sequential offer_number starting from 1, fiscal_year = 2026");
            
            $preview2026 = $offers2026->take(5)->map(function($o, $index) {
                return [
                    $o->id,
                    $index + 1, // Sequential starting from 1
                    2026,
                    $o->created_at
                ];
            });
            
            $this->table(
                ['ID', 'New Offer Number', 'Fiscal Year', 'Created At'],
                $preview2026
            );
            
            if ($offers2026->count() > 5) {
                $this->info("  ... and " . ($offers2026->count() - 5) . " more");
            }
            $this->newLine();
        }

        // Other years (before 2025)
        $offersOther = DB::table('offers')
            ->whereYear('created_at', '<', 2025)
            ->orderBy('id')
            ->get(['id', 'created_at']);

        if ($offersOther->count() > 0) {
            $this->info("Pre-2025 Offers ({$offersOther->count()} total):");
            $this->info("  - Will set offer_number = id, fiscal_year = YEAR(created_at)");
            
            $previewOther = $offersOther->take(5)->map(fn($o) => [
                $o->id,
                $o->id,
                date('Y', strtotime($o->created_at)),
                $o->created_at
            ]);
            
            $this->table(
                ['ID', 'New Offer Number', 'Fiscal Year', 'Created At'],
                $previewOther
            );
            
            if ($offersOther->count() > 5) {
                $this->info("  ... and " . ($offersOther->count() - 5) . " more");
            }
            $this->newLine();
        }

        // Check for potential issues
        $this->info('=== Validation Checks ===');
        
        // Check for NULL created_at
        $nullCreatedAt = DB::table('offers')->whereNull('created_at')->count();
        if ($nullCreatedAt > 0) {
            $this->error("WARNING: {$nullCreatedAt} offers have NULL created_at - these will fail!");
        } else {
            $this->info('✓ All offers have valid created_at dates');
        }

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Total offers to migrate: {$totalOffers}");
        $this->info("2025 offers (offer_number = id): " . $offers2025->count());
        $this->info("2026 offers (sequential from 1): " . $offers2026->count());
        $this->info("Pre-2025 offers (offer_number = id): " . $offersOther->count());

        if ($isDryRun) {
            $this->newLine();
            $this->warn('This was a DRY RUN - no changes were made.');
            $this->info('Run without --dry-run to apply the migration.');
        }

        return 0;
    }
}
