<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // The table is created by 2024_01_23_000000_create_catalog_items_table.
        // Dropping it here breaks fresh migrations because older tables already reference it.
    }

    public function down()
    {
        // Keep rollback non-destructive because many later migrations reference this table.
    }
};
