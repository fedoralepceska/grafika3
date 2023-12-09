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
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('additionalArt')->default(false);
            $table->boolean('rush')->default(false);
            $table->boolean('revisedArtComplete')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('additionalArt');
            $table->dropColumn('rush');
            $table->dropColumn('revisedArtComplete');
        });
    }
};
