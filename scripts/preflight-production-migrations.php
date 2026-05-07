<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$failures = [];
$warnings = [];

$line = static function (string $message = ''): void {
    echo $message . PHP_EOL;
};

$ok = static function (string $message) use ($line): void {
    $line('[OK] ' . $message);
};

$warn = static function (string $message) use (&$warnings, $line): void {
    $warnings[] = $message;
    $line('[WARN] ' . $message);
};

$fail = static function (string $message) use (&$failures, $line): void {
    $failures[] = $message;
    $line('[FAIL] ' . $message);
};

$migrationRan = static function (string $migration): bool {
    if (! Schema::hasTable('migrations')) {
        return false;
    }

    return DB::table('migrations')->where('migration', $migration)->exists();
};

$hasColumns = static function (string $table, array $columns): array {
    return array_values(array_filter(
        $columns,
        static fn (string $column): bool => ! Schema::hasColumn($table, $column),
    ));
};

$hasIndex = static function (string $table, string $index): ?bool {
    if (DB::connection()->getDriverName() !== 'mysql') {
        return null;
    }

    return DB::table('information_schema.STATISTICS')
        ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
        ->where('TABLE_NAME', $table)
        ->where('INDEX_NAME', $index)
        ->exists();
};

$line('Production migration preflight');
$line('Database: ' . DB::connection()->getDatabaseName());
$line('This script is read-only and does not run migrations.');
$line();

if (! Schema::hasTable('migrations')) {
    $fail('The migrations table does not exist.');
} else {
    $ok('The migrations table exists.');
}

$catalogMigration = '2024_01_23_000000_create_catalog_items_table';
$catalogMigrationRan = $migrationRan($catalogMigration);
$catalogItemsExists = Schema::hasTable('catalog_items');

if ($catalogItemsExists) {
    $ok('catalog_items table exists.');
} else {
    $fail('catalog_items table is missing.');
}

if ($catalogMigrationRan) {
    $ok($catalogMigration . ' is already recorded as ran.');
} elseif ($catalogItemsExists) {
    $ok($catalogMigration . ' is pending and will no-op because catalog_items already exists.');
} else {
    $fail($catalogMigration . ' is pending and would create catalog_items. Stop and inspect production schema first.');
}

$oldCatalogMigration = '2024_12_02_111238_drop_and_recreate_catalog_items_table';
if ($migrationRan($oldCatalogMigration)) {
    $ok($oldCatalogMigration . ' is already recorded as ran.');
} else {
    $warn($oldCatalogMigration . ' is pending. The current file is non-destructive, but this is unexpected for production.');
}

$dashboardMigration = '2026_05_07_151500_add_dashboard_indexes_to_invoices_table';
$dashboardMigrationRan = $migrationRan($dashboardMigration);
$invoiceIndexes = [
    'invoices_dashboard_status_year_created_idx',
    'invoices_dashboard_year_created_idx',
    'invoices_dashboard_user_year_created_idx',
];

if (! Schema::hasTable('invoices')) {
    $fail('invoices table is missing.');
} else {
    $ok('invoices table exists.');

    $missingInvoiceColumns = $hasColumns('invoices', [
        'status',
        'fiscal_year',
        'created_at',
        'created_by',
    ]);

    if ($missingInvoiceColumns === []) {
        $ok('invoices has all columns required by dashboard indexes.');
    } else {
        $fail('invoices is missing required columns: ' . implode(', ', $missingInvoiceColumns));
    }

    if ($dashboardMigrationRan) {
        $ok($dashboardMigration . ' is already recorded as ran.');
    } else {
        $ok($dashboardMigration . ' is pending.');

        foreach ($invoiceIndexes as $index) {
            $indexExists = $hasIndex('invoices', $index);

            if ($indexExists === null) {
                $warn('Could not inspect index ' . $index . ' because this check only supports MySQL.');
            } elseif ($indexExists) {
                $fail('Index already exists and migration would try to create it again: ' . $index);
            } else {
                $ok('Index is not present yet and can be created: ' . $index);
            }
        }
    }
}

$line();

if ($failures !== []) {
    $line('Preflight failed. Do not run php artisan migrate yet.');
    exit(1);
}

if ($warnings !== []) {
    $line('Preflight passed with warnings. Review warnings before running php artisan migrate --force.');
    exit(0);
}

$line('Preflight passed. It is safe to proceed with php artisan migrate --force.');
