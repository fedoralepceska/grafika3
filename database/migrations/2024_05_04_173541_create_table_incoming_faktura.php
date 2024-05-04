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
        Schema::create('incoming_faktura', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->integer('warehouse')->nullable();
            $table->integer('cost_type')->nullable();
            $table->integer('billing_type')->nullable();
            $table->string('description')->nullable();
            $table->string('comment')->nullable();
            $table->float('amount')->nullable();
            $table->float('tax')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_incoming_faktura');
    }
};
