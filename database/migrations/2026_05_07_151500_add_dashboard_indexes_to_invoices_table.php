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
            $table->index(['status', 'fiscal_year', 'created_at'], 'invoices_dashboard_status_year_created_idx');
            $table->index(['fiscal_year', 'created_at'], 'invoices_dashboard_year_created_idx');
            $table->index(['created_by', 'fiscal_year', 'created_at'], 'invoices_dashboard_user_year_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_dashboard_status_year_created_idx');
            $table->dropIndex('invoices_dashboard_year_created_idx');
            $table->dropIndex('invoices_dashboard_user_year_created_idx');
        });
    }
};
