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
        // Group fakturas by year and assign sequential numbers
        $years = DB::table('faktura')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->orderBy('year')
            ->pluck('year');

        foreach ($years as $year) {
            $fakturas = DB::table('faktura')
                ->whereYear('created_at', $year)
                ->orderBy('id')
                ->get();

            $seq = 1;
            foreach ($fakturas as $faktura) {
                DB::table('faktura')
                    ->where('id', $faktura->id)
                    ->update([
                        'faktura_number' => $seq++,
                        'fiscal_year' => $year,
                    ]);
            }
        }

        // Now add NOT NULL constraints and unique index
        Schema::table('faktura', function (Blueprint $table) {
            $table->integer('faktura_number')->nullable(false)->change();
            $table->integer('fiscal_year')->nullable(false)->change();
            $table->unique(['faktura_number', 'fiscal_year'], 'idx_faktura_fiscal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faktura', function (Blueprint $table) {
            $table->dropUnique('idx_faktura_fiscal');
            $table->integer('faktura_number')->nullable()->change();
            $table->integer('fiscal_year')->nullable()->change();
        });
    }
};
