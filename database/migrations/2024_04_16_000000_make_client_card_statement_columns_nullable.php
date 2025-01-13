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
        Schema::table('client_card_statement', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('functionInfo')->nullable()->change();
            $table->integer('phone')->nullable()->change();
            $table->integer('fax')->nullable()->change();
            $table->integer('mobile_phone')->nullable()->change();
            $table->string('edb')->nullable()->change();
            $table->string('account')->nullable()->change();
            $table->string('bank')->nullable()->change();
            $table->float('initial_statement')->nullable()->change();
            $table->float('initial_cash')->nullable()->change();
            $table->float('credit_limit')->nullable()->change();
            $table->float('payment_deadline')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_card_statement', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('functionInfo')->nullable(false)->change();
            $table->integer('phone')->nullable(false)->change();
            $table->integer('fax')->nullable(false)->change();
            $table->integer('mobile_phone')->nullable(false)->change();
            $table->string('edb')->nullable(false)->change();
            $table->string('account')->nullable(false)->change();
            $table->string('bank')->nullable(false)->change();
            $table->float('initial_statement')->nullable(false)->change();
            $table->float('initial_cash')->nullable(false)->change();
            $table->float('credit_limit')->nullable(false)->change();
            $table->float('payment_deadline')->nullable(false)->change();
        });
    }
}; 