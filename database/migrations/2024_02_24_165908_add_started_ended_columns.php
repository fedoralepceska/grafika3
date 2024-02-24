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
            $table->unsignedBigInteger('started_by')->nullable(); // Assuming started_by can be NULL if not assigned to any user
            $table->foreign('started_by')->references('id')->on('users')->onDelete('set null'); // Assuming users table exists and you have 'id' column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['started_by']);
            $table->dropColumn('started_by');
        });
    }
};
