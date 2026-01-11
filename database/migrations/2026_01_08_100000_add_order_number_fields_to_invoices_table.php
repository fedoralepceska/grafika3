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
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('order_number')->nullable()->after('id');
            $table->integer('fiscal_year')->nullable()->after('order_number');
            $table->boolean('archived')->default(false)->after('fiscal_year');
            $table->timestamp('archived_at')->nullable()->after('archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['order_number', 'fiscal_year', 'archived', 'archived_at']);
        });
    }
};
