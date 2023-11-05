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
        Schema::create('history_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id'); // Foreign key to the invoices table
            $table->text('action'); // Descriptive text of what happened
            $table->unsignedBigInteger('performed_by'); // User who performed the action
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_logs');
    }
};
