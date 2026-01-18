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
        Schema::create('client_year_end_entries', function (Blueprint $table) {
            $table->id();
            $table->integer('fiscal_year');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('client_card_statement_id')->constrained('client_card_statement')->onDelete('cascade');
            
            // Snapshot of initial balance at time of census creation
            $table->decimal('initial_balance', 15, 2)->default(0);
            
            // Calculated totals from transactions
            $table->decimal('total_output_invoices', 15, 2)->default(0);
            $table->decimal('total_trade_invoices', 15, 2)->default(0);
            $table->decimal('total_statement_expenses', 15, 2)->default(0);
            $table->decimal('total_incoming_invoices', 15, 2)->default(0);
            $table->decimal('total_statement_income', 15, 2)->default(0);
            
            // Calculated and adjusted balances
            $table->decimal('calculated_balance', 15, 2)->default(0);
            $table->decimal('adjusted_balance', 15, 2)->nullable();
            
            // Status tracking
            $table->enum('status', ['pending', 'ready_to_close', 'closed'])->default('pending');
            $table->timestamp('closed_at')->nullable();
            
            $table->timestamps();
            
            // Ensure one entry per client per year
            $table->unique(['fiscal_year', 'client_id']);
            $table->index(['fiscal_year', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_year_end_entries');
    }
};
