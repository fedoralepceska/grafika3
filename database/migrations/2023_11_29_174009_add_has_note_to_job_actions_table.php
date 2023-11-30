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
        Schema::table('job_actions', function (Blueprint $table) {
            $table->boolean('hasNote')->default(false)->after('status'); // Adds the 'hasNote' column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_actions', function (Blueprint $table) {
            $table->dropColumn('hasNote'); // Removes the 'hasNote' column if the migration is rolled back
        });
    }
};
