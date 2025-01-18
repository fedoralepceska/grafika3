<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set default cost_price to 0 for existing catalog items
        DB::table('catalog_items')->update([
            'cost_price' => 0
        ]);
    }

    public function down(): void
    {
        // No need to do anything in down() as the column will be dropped
        // by the previous migration's down method
    }
};
