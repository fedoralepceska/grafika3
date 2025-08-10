<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_item_subcategory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('catalog_item_id');
            $table->unsignedBigInteger('subcategory_id');
            $table->timestamps();

            $table->unique(['catalog_item_id', 'subcategory_id'], 'catalog_item_subcategory_unique');
            $table->foreign('catalog_item_id')->references('id')->on('catalog_items')->onDelete('cascade');
            $table->foreign('subcategory_id')->references('id')->on('subcategories')->onDelete('cascade');
        });

        // Backfill existing single subcategory_id into the pivot table if the column exists
        if (Schema::hasColumn('catalog_items', 'subcategory_id')) {
            $rows = DB::table('catalog_items')
                ->whereNotNull('subcategory_id')
                ->select('id as catalog_item_id', 'subcategory_id')
                ->get();

            foreach ($rows as $row) {
                // Insert ignoring duplicates
                DB::table('catalog_item_subcategory')->updateOrInsert(
                    [
                        'catalog_item_id' => $row->catalog_item_id,
                        'subcategory_id' => $row->subcategory_id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_item_subcategory');
    }
};


