<?php

use Fleetbase\Pallet\Http\Controllers\MetricsController;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;

/**
 * Every metrics endpoint backs at least one dashboard widget, so a 500 here
 * is a blank or broken widget on the console home screen.
 */
function metricsEndpoints(): array
{
    return [
        'kpis',
        'inventoryHealth',
        'warehouseUtilization',
        'stockMovement',
        'fulfillmentWorkload',
        'reorderRisk',
        'inventorySummary',
        'lowStock',
        'poStatus',
        'soStatus',
        'stockValue',
        'expiringStock',
        'topProducts',
    ];
}

function seedMetricsCompany(): string
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Metrics WH', 'code' => 'MWH-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Metrics Product', 'sku' => 'MP-' . uniqid(), 'unit_cost' => 3]);

    $inventory = Inventory::create([
        'company_uuid'       => $company,
        'product_uuid'       => $product->uuid,
        'warehouse_uuid'     => $warehouse->uuid,
        'quantity'           => 12,
        'reserved_quantity'  => 4,
        'available_quantity' => 8,
        'min_quantity'       => 20,
        'reorder_point'      => 15,
        'unit_cost'          => 3,
        'status'             => 'active',
        'expiry_date_at'     => now()->addDays(10),
    ]);

    $inventory->recordStockTransaction('received', 12);

    PurchaseOrder::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'status'         => 'pending',
    ]);

    return $company;
}

test('every metrics endpoint responds for a company with no data', function (string $endpoint) {
    session(['company' => (string) Illuminate\Support\Str::uuid()]);

    $response = (new MetricsController())->{$endpoint}(Request::create('/', 'GET'));

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBeArray();
})->with(metricsEndpoints());

test('every metrics endpoint responds with seeded inventory', function (string $endpoint) {
    seedMetricsCompany();

    $response = (new MetricsController())->{$endpoint}(Request::create('/', 'GET'));

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBeArray();
})->with(metricsEndpoints());

test('kpis report the seeded stock accurately', function () {
    seedMetricsCompany();

    $payload = json_decode((new MetricsController())->kpis(Request::create('/', 'GET'))->getContent(), true);

    expect($payload['available_units']['value'])->toBe(8)
        ->and($payload['reserved_units']['value'])->toBe(4)
        ->and($payload['stock_value']['value'])->toEqual(36.0)
        ->and($payload['low_stock']['value'])->toBe(1)
        ->and($payload['expiring_soon']['value'])->toBe(1)
        ->and($payload['open_pos']['value'])->toBe(1);
});

test('metrics never leak another company data', function () {
    seedMetricsCompany();

    // a different tenant asking the same question must see nothing
    session(['company' => (string) Illuminate\Support\Str::uuid()]);
    $payload = json_decode((new MetricsController())->kpis(Request::create('/', 'GET'))->getContent(), true);

    expect($payload['available_units']['value'])->toBe(0)
        ->and($payload['reserved_units']['value'])->toBe(0)
        ->and($payload['low_stock']['value'])->toBe(0)
        ->and($payload['open_pos']['value'])->toBe(0);
});

test('reorder risk lists products at or below their reorder point', function () {
    $company   = seedMetricsCompany();
    $warehouse = Warehouse::where('company_uuid', $company)->first();

    // a product comfortably above its reorder point must not appear
    $healthy = Product::create(['company_uuid' => $company, 'name' => 'Healthy', 'sku' => 'HP-' . uniqid(), 'reorder_point' => 5]);
    Inventory::create([
        'company_uuid'       => $company,
        'product_uuid'       => $healthy->uuid,
        'warehouse_uuid'     => $warehouse->uuid,
        'quantity'           => 50,
        'reserved_quantity'  => 0,
        'available_quantity' => 50,
        'status'             => 'active',
    ]);

    $atRisk = Product::create(['company_uuid' => $company, 'name' => 'AtRisk', 'sku' => 'AR-' . uniqid(), 'reorder_point' => 25]);
    Inventory::create([
        'company_uuid'       => $company,
        'product_uuid'       => $atRisk->uuid,
        'warehouse_uuid'     => $warehouse->uuid,
        'quantity'           => 4,
        'reserved_quantity'  => 0,
        'available_quantity' => 4,
        'status'             => 'active',
    ]);

    $payload = json_decode((new MetricsController())->reorderRisk(Request::create('/', 'GET'))->getContent(), true);
    $names   = array_column($payload['products'], 'name');

    expect($names)->toContain('AtRisk')
        ->and($names)->not->toContain('Healthy');

    $risk = collect($payload['products'])->firstWhere('name', 'AtRisk');
    expect($risk['available_stock'])->toBe(4)
        ->and($risk['shortage'])->toBe(21);
});

/*
 * A model-level $withCount applies to EVERY query on that model, including the
 * GROUP BY aggregates the metrics endpoints build. Eloquent prepends `table.*` and
 * the count subquery to the select list, and MySQL's only_full_group_by then
 * rejects the whole statement — warehouse-utilization returned a 500 in the console
 * for exactly this reason while this suite stayed green, because SQLite does not
 * enforce that rule.
 *
 * The counts the console needs are requested explicitly by the controllers that
 * want them, which is also the only form that survives searchBuilder()'s
 * select(['*']). Declaring one on a model is therefore never the right fix.
 */
test('no pallet model declares a global withCount', function () {
    foreach (glob(__DIR__ . '/../src/Models/*.php') as $file) {
        $name  = basename($file, '.php');
        $class = 'Fleetbase\\Pallet\\Models\\' . $name;

        if (!class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->hasProperty('withCount')) {
            continue;
        }

        $property = $reflection->getProperty('withCount');
        $property->setAccessible(true);

        expect($property->getValue(new $class()))->toBe(
            [],
            $name . ' declares a global $withCount, which breaks any GROUP BY aggregate over it under only_full_group_by'
        );
    }
});
