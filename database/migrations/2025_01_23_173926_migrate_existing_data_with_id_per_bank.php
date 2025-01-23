<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certificate', function (Blueprint $table) {
            // Populate the new column
            $bankStatements = \Illuminate\Support\Facades\DB::table('certificate')
                ->select('id', 'bank', 'date')
                ->orderBy('bank')
                ->orderBy('date')
                ->get()
                ->groupBy('bank');

            foreach ($bankStatements as $bank => $statements) {
                $idPerBank = 1;
                foreach ($statements as $statement) {
                    \Illuminate\Support\Facades\DB::table('certificate')
                        ->where('id', $statement->id)
                        ->update(['id_per_bank' => $idPerBank++]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate', function (Blueprint $table) {
            //
        });
    }
};
