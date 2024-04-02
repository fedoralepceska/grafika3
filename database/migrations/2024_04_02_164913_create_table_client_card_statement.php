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
        Schema::create('client_card_statement', function (Blueprint $table) {
            $table->id();
            $table->string('name')-> nullable();
            $table->string('function')-> nullable();
            $table->integer('phone')-> nullable();
            $table->integer('fax')-> nullable();
            $table->integer('mobile_phone')-> nullable();
            $table->string('edb')-> nullable();
            $table->integer('account')-> nullable();
            $table->string('bank')-> nullable();
            $table->float('initial_statement')-> nullable();
            $table->float('initial_cash')-> nullable();
            $table->float('credit_limit')-> nullable();
            $table->float('payment_deadline')-> nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_card_statement');
    }
};
