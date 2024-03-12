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
        Schema::table('jobs', function (Blueprint $table) {
            $table->float('width')->nullable()->change();
            $table->float('height')->nullable()->change();
            $table->integer('estimatedTime')->nullable()->change();
            $table->string('shippingInfo')->nullable()->change();
            $table->integer('quantity')->nullable()->change();
            $table->integer('copies')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->float('width')->nullable(false)->change();
            $table->float('height')->nullable(false)->change();
            $table->integer('estimatedTime')->nullable(false)->change();
            $table->string('shippingInfo')->nullable(false)->change();
            $table->integer('quantity')->nullable(false)->change();
            $table->integer('copies')->nullable(false)->change();
        });
    }
};
