<?php

namespace Server\tests;

use Fleetbase\Pallet\Models\Inventory;
use Illuminate\Support\Str;

/*
 * `supplier` sat in Inventory's $fillable while the table only has supplier_uuid.
 * The console's inventory serializer embeds the supplier relation, so every update
 * carried a `supplier` key, mass assignment let it through, and MySQL rejected the
 * whole statement with "Unknown column 'supplier' in 'field list'" — no inventory
 * record could be saved from the console at all.
 */
test('no Pallet model declares a fillable attribute without a column behind it', function () {
    $offenders = [];

    foreach (glob(__DIR__ . '/../src/Models/*.php') as $file) {
        $class = 'Fleetbase\\Pallet\\Models\\' . basename($file, '.php');

        if (!class_exists($class)) {
            continue;
        }

        $model = new $class();

        if (!$model instanceof \Illuminate\Database\Eloquent\Model || !$model->getFillable()) {
            continue;
        }

        $builder = $model->getConnection()->getSchemaBuilder();

        // Only Pallet's own tables. Models mapped onto core tables (Supplier lives
        // on `vendors`) would be judged against this harness's shim rather than the
        // real schema, and the shim is deliberately partial.
        if (!str_starts_with($model->getTable(), 'pallet_') || !$builder->hasTable($model->getTable())) {
            continue;
        }

        $missing = array_values(array_diff($model->getFillable(), $builder->getColumnListing($model->getTable())));

        if ($missing) {
            $offenders[basename($file, '.php')] = $missing;
        }
    }

    expect($offenders)->toBe([], 'a fillable name with no column reaches the UPDATE and MySQL rejects the whole statement');
});

test('an inventory record survives an update carrying an embedded relation key', function () {
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $inventory = Inventory::create(['company_uuid' => $company, 'quantity' => 5]);

    // the shape the console actually posts
    $inventory->update(collect(['quantity' => 9, 'supplier' => null])->only($inventory->getFillable())->all());

    expect($inventory->fresh()->quantity)->toBe(9);
});
