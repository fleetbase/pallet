<?php

use Fleetbase\Pallet\Support\Reporting\PalletReportSchema;
use Fleetbase\Support\Reporting\ReportSchemaRegistry;
use Illuminate\Support\Facades\Schema;

/*
 * Every column the report schema advertises must exist on the table it names.
 *
 * The first version of PalletReportSchema was written from the Ember models rather than
 * the database, and several columns did not exist: pallet_inventories has no
 * `batch_number`, pallet_products has no `price`, purchase orders record
 * `order_created_at` rather than `order_date_at`, sales orders carry no `currency`, and
 * there is no pallet_suppliers table at all — suppliers are FleetOps' `vendors`.
 *
 * None of that was visible in the interface. The builder listed the tables, resolved the
 * columns and let a report be built and saved; it only failed at execution, with
 * "Unknown column ... in 'field list'", which the preview swallowed into an empty table.
 * A schema that advertises a column the database does not have is a broken report that
 * looks fine until someone runs it.
 */
test('every registered report column exists on its table', function () {
    $registry = new ReportSchemaRegistry();
    (new PalletReportSchema())->registerReportSchema($registry);

    $problems = [];

    foreach ($registry->getRegisteredTableNames() as $tableName) {
        if (!Schema::hasTable($tableName)) {
            $problems[] = "table {$tableName} does not exist";
            continue;
        }

        $actual = Schema::getColumnListing($tableName);

        foreach ($registry->getTable($tableName)->toArray()['columns'] as $column) {
            if (!in_array($column['name'], $actual, true)) {
                $problems[] = "{$tableName}.{$column['name']}";
            }
        }
    }

    expect($problems)->toBe([]);
});

test('every starter report selects columns its table actually has', function () {
    // The seeder silently drops a column it cannot find in the registry, so a typo here
    // produces a report that is quietly missing a field rather than one that fails.
    $registry = new ReportSchemaRegistry();
    (new PalletReportSchema())->registerReportSchema($registry);

    $command     = new Fleetbase\Pallet\Console\Commands\SeedStarterReports();
    $reflection  = new ReflectionClass($command);
    $definitions = $reflection->getMethod('definitions');
    $definitions->setAccessible(true);

    $problems = [];

    foreach ($definitions->invoke($command) as $definition) {
        if (!$registry->hasTable($definition['table'])) {
            $problems[] = "{$definition['title']}: unknown table {$definition['table']}";
            continue;
        }

        $available = collect($registry->getTable($definition['table'])->toArray()['columns'])->pluck('name')->all();

        foreach ($definition['columns'] as $column) {
            if (!in_array($column, $available, true)) {
                $problems[] = "{$definition['title']}: {$definition['table']}.{$column}";
            }
        }
    }

    expect($problems)->toBe([]);
});
