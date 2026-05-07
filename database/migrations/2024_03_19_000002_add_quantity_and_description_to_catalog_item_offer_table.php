<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('catalog_item_offer')) {
            return;
        }

        Schema::table('catalog_item_offer', function (Blueprint $table) {
            if (! Schema::hasColumn('catalog_item_offer', 'quantity')) {
                $table->integer('quantity')->default(1)->after('catalog_item_id');
            }

            if (! Schema::hasColumn('catalog_item_offer', 'description')) {
                $table->text('description')->nullable()->after('quantity');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('catalog_item_offer')) {
            return;
        }

        Schema::table('catalog_item_offer', function (Blueprint $table) {
            if (Schema::hasColumn('catalog_item_offer', 'quantity')) {
                $table->dropColumn('quantity');
            }

            if (Schema::hasColumn('catalog_item_offer', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};