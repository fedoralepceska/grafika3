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
        Schema::table('priemnica_articles', function (Blueprint $table) {
            $table->decimal('custom_price', 15, 5)->nullable()->after('quantity');
            $table->string('custom_tax_type', 1)->nullable()->after('custom_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('priemnica_articles', function (Blueprint $table) {
            $table->dropColumn(['custom_price', 'custom_tax_type']);
        });
    }
};
