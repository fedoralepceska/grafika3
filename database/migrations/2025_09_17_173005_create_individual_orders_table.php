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
		Schema::create('individual_orders', function (Blueprint $table) {
			$table->id();
			$table->foreignId('invoice_id')->constrained('invoices')->unique()->cascadeOnDelete();
			$table->foreignId('client_id')->constrained('clients');
			$table->decimal('total_amount', 12, 2)->default(0);
			$table->enum('paid_status', ['paid', 'unpaid'])->default('unpaid');
			$table->text('notes')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('individual_orders');
	}
};
