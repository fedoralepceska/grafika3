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
        // Add offer_number and fiscal_year columns if they don't exist
        Schema::table('offers', function (Blueprint $table) {
            if (!Schema::hasColumn('offers', 'offer_number')) {
                $table->integer('offer_number')->nullable()->after('id');
            }
            if (!Schema::hasColumn('offers', 'fiscal_year')) {
                $table->integer('fiscal_year')->nullable()->after('offer_number');
            }
        });

        // Backfill 2025 offers: offer_number = id, fiscal_year = 2025
        DB::statement("
            UPDATE offers 
            SET offer_number = id, fiscal_year = 2025 
            WHERE YEAR(created_at) = 2025
        ");

        // Backfill 2026 offers with sequential numbering starting from 1
        $offers2026 = DB::table('offers')
            ->whereYear('created_at', 2026)
            ->orderBy('id')
            ->get();

        $seq = 1;
        foreach ($offers2026 as $offer) {
            DB::table('offers')
                ->where('id', $offer->id)
                ->update([
                    'offer_number' => $seq++,
                    'fiscal_year' => 2026,
                ]);
        }

        // Handle any offers from other years (before 2025)
        DB::statement("
            UPDATE offers 
            SET offer_number = id, fiscal_year = YEAR(created_at) 
            WHERE fiscal_year IS NULL
        ");

        // Now add NOT NULL constraints and unique index
        Schema::table('offers', function (Blueprint $table) {
            $table->integer('offer_number')->nullable(false)->change();
            $table->integer('fiscal_year')->nullable(false)->change();
            $table->unique(['offer_number', 'fiscal_year'], 'idx_offer_fiscal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropUnique('idx_offer_fiscal');
            $table->dropColumn(['offer_number', 'fiscal_year']);
        });
    }
};
