<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('offers')) {
            return;
        }

        Schema::table('offers', function (Blueprint $table) {
            if (! Schema::hasColumn('offers', 'client_id')) {
                $table->foreignId('client_id')->after('id')->constrained()->onDelete('cascade');
            }

            if (! Schema::hasColumn('offers', 'validity_days')) {
                $table->integer('validity_days')->default(30)->after('description');
            }

            if (! Schema::hasColumn('offers', 'production_start_date')) {
                $table->date('production_start_date')->after('validity_days');
            }

            if (! Schema::hasColumn('offers', 'production_end_date')) {
                $table->date('production_end_date')->after('production_start_date');
            }

            if (! Schema::hasColumn('offers', 'status')) {
                $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending')->after('price3');
            }

            if (! Schema::hasColumn('offers', 'decline_reason')) {
                $table->text('decline_reason')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('offers')) {
            return;
        }

        Schema::table('offers', function (Blueprint $table) {
            if (Schema::hasColumn('offers', 'client_id')) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            }

            foreach ([
                'validity_days',
                'production_start_date',
                'production_end_date',
                'status',
                'decline_reason',
            ] as $column) {
                if (Schema::hasColumn('offers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};