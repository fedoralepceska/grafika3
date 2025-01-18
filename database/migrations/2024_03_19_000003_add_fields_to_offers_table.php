<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->foreignId('client_id')->after('id')->constrained()->onDelete('cascade');
            $table->integer('validity_days')->default(30)->after('description');
            $table->date('production_start_date')->after('validity_days');
            $table->date('production_end_date')->after('production_start_date');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending')->after('price3');
            $table->text('decline_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn([
                'client_id',
                'validity_days',
                'production_start_date',
                'production_end_date',
                'status',
                'decline_reason'
            ]);
        });
    }
};