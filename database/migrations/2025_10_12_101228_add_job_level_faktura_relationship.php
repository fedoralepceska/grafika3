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
        // Add faktura_id directly to jobs table for job-level invoice assignment
        Schema::table('jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('faktura_id')->nullable()->after('invoice_id');
            $table->foreign('faktura_id')->references('id')->on('faktura')->onDelete('set null');
        });

        // Add split tracking to faktura table
        Schema::table('faktura', function (Blueprint $table) {
            $table->boolean('is_split_invoice')->default(false);
            $table->string('split_group_identifier')->nullable();
            $table->unsignedBigInteger('parent_order_id')->nullable(); // Reference to original order
            $table->foreign('parent_order_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['faktura_id']);
            $table->dropColumn('faktura_id');
        });

        Schema::table('faktura', function (Blueprint $table) {
            $table->dropForeign(['parent_order_id']);
            $table->dropColumn(['is_split_invoice', 'split_group_identifier', 'parent_order_id']);
        });
    }
};