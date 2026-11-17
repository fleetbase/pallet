<?php

use Fleetbase\Pallet\Http\Controllers\MetricsController;
use Fleetbase\Pallet\Http\Filter\InventoryFilter;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * The Low Stock and Expired Stock screens run the summarized inventory listing
 * through InventoryFilter::view.
 *
 * Low stock used to read `total_quantity < minimum_quantity`, which disagreed
 * with the dashboard KPI those screens are reached from: that counts
 * `min_quantity > 0 AND available_quantity <= min_quantity` and labels itself
 * "At or below minimum". Stock sitting exactly at its reorder point was counted
 * on the dashboard and then missing from the screen.
 */
function stockView(string $company, string $view): array
{
    $request = Request::create('/pallet/int/v1/inventories?view=' . $view, 'GET');
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('company', $company);

    $route = new Route(['GET'], 'pallet/int/v1/inventories', ['namespace' => '\\Fleetbase\\Pallet']);
    $request->setRouteResolver(fn () => $route);

    return (new InventoryFilter($request))->apply(Inventory::query()->summarizeByProduct())->get()->all();
}

function seedStock(string $company, array $attributes): Inventory
{
    $warehouse = Warehouse::firstOrCreate(
        ['company_uuid' => $company, 'code' => 'VIEW-WH'],
        ['name' => 'Views WH']
    );
    $product = Product::create([
        'company_uuid' => $company,
        'name'         => $attributes['name'],
        'sku'          => 'VIEW-' . uniqid(),
    ]);

    return Inventory::create(array_merge([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'product_uuid'   => $product->uuid,
        'status'         => 'active',
    ], Illuminate\Support\Arr::except($attributes, ['name'])));
}

test('stock sitting exactly at its reorder point counts as low', function () {
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    seedStock($company, ['name' => 'At minimum', 'quantity' => 5, 'min_quantity' => 5]);

    expect(stockView($company, 'low_stock'))->toHaveCount(1);
});

test('the low stock screen agrees with the dashboard low stock count', function () {
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    seedStock($company, ['name' => 'At minimum', 'quantity' => 5, 'min_quantity' => 5]);
    seedStock($company, ['name' => 'Below minimum', 'quantity' => 1, 'min_quantity' => 4]);
    seedStock($company, ['name' => 'Healthy', 'quantity' => 50, 'min_quantity' => 4]);

    $dashboardCount = (new ReflectionClass(MetricsController::class))
        ->getMethod('lowStockQuery');
    $dashboardCount->setAccessible(true);
    $expected = $dashboardCount->invoke(new MetricsController(), $company)->count();

    expect($expected)->toBe(2)
        ->and(stockView($company, 'low_stock'))->toHaveCount($expected);
});

test('products with no reorder point set are not reported as low', function () {
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    seedStock($company, ['name' => 'No reorder point', 'quantity' => 0, 'min_quantity' => 0]);

    expect(stockView($company, 'low_stock'))->toBeEmpty();
});

test('reserved stock counts against the low stock threshold', function () {
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    // 10 on hand but 8 reserved leaves 2 available, under a minimum of 5.
    seedStock($company, ['name' => 'Mostly reserved', 'quantity' => 10, 'reserved_quantity' => 8, 'min_quantity' => 5]);

    expect(stockView($company, 'low_stock'))->toHaveCount(1);
});

test('the expired stock view returns only stock past its expiry', function () {
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    seedStock($company, ['name' => 'Expired', 'quantity' => 5, 'expiry_date_at' => now()->subDay()]);
    seedStock($company, ['name' => 'Fresh', 'quantity' => 5, 'expiry_date_at' => now()->addMonth()]);
    seedStock($company, ['name' => 'No expiry', 'quantity' => 5]);

    $results = stockView($company, 'expired_stock');

    expect($results)->toHaveCount(1);
});
