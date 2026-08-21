<?php

use Fleetbase\Pallet\Http\Controllers\WarehouseController;
use Fleetbase\Pallet\Http\Resources\Internal\v1\Warehouse as WarehouseResource;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;

/**
 * The warehouse list renders a STOCK ITEMS column, but the resource emitted no
 * such field, so the column showed "-" for every warehouse regardless of how
 * much stock it held.
 */
function warehouseResourcePayload(int $inventoryRows): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Counted WH', 'code' => 'CNT-' . uniqid()]);

    for ($i = 0; $i < $inventoryRows; $i++) {
        $product = Product::create(['company_uuid' => $company, 'name' => 'P' . $i, 'sku' => 'CNT-' . uniqid()]);

        Inventory::create([
            'company_uuid'   => $company,
            'warehouse_uuid' => $warehouse->uuid,
            'product_uuid'   => $product->uuid,
            'quantity'       => 10,
            'status'         => 'active',
        ]);
    }

    // Go through the same query shape the controller uses. A bare find() would only
    // carry the count while the model declared a global $withCount, which it must not
    // — see MetricsTest, 'no pallet model declares a global withCount'.
    $record = Warehouse::withCount('inventories')->where('uuid', $warehouse->uuid)->first();

    return (new WarehouseResource($record))->toArray(Request::create('/'));
}

test('a warehouse reports how many stock items it holds', function () {
    expect(warehouseResourcePayload(3)['stock_items'])->toBe(3);
});

test('a warehouse with no stock reports zero rather than omitting the field', function () {
    $payload = warehouseResourcePayload(0);

    expect($payload)->toHaveKey('stock_items')
        ->and($payload['stock_items'])->toBe(0);
});

/*
 * The count has to survive the LIST query, not just a find().
 *
 * Model::newQuery() adds the $withCount subquery, then searchBuilder() calls
 * select(['*']) which replaces the entire select list and discards it — so the
 * list reported zero stock items for every warehouse while a find() reported
 * the truth. Exercising only the resource hid that completely.
 */
test('the stock items count survives the list query, not just a direct find', function () {
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Listed WH', 'code' => 'LST-' . uniqid()]);

    foreach (range(1, 2) as $i) {
        $product = Product::create(['company_uuid' => $company, 'name' => 'LP' . $i, 'sku' => 'LST-' . uniqid()]);
        Inventory::create([
            'company_uuid'   => $company,
            'warehouse_uuid' => $warehouse->uuid,
            'product_uuid'   => $product->uuid,
            'quantity'       => 5,
            'status'         => 'active',
        ]);
    }

    // Build the list query the way the controller does, applying the same hook.
    $query = Warehouse::query()->select(['*']);
    (new WarehouseController())->onQueryRecord($query);

    $listed = $query->where('company_uuid', $company)->first();

    expect($listed->inventories_count)->not->toBeNull()
        ->and((int) $listed->inventories_count)->toBe(2)
        ->and((new WarehouseResource($listed))->toArray(Request::create('/'))['stock_items'])->toBe(2);
});

/*
 * A warehouse created with no address was still given a Place, because the place
 * payload always carries the warehouse name and the emptiness check could therefore
 * never fail. Utils::getAddressStringForPlace lists `name` first among the address
 * parts and uppercases each one, so the warehouses list rendered the warehouse's own
 * name, shouted, in its ADDRESS column.
 */
test('a warehouse created without an address gets no place', function () {
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $request = Request::create('/pallet/int/v1/warehouses', 'POST', [
        'warehouse' => ['name' => 'No Address Warehouse', 'code' => 'NOADDR-' . uniqid()],
    ]);
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('company', $company);

    (new WarehouseController())->createRecord($request);

    $warehouse = Warehouse::where('name', 'No Address Warehouse')->first();

    expect($warehouse)->not->toBeNull()
        ->and($warehouse->place_uuid)->toBeNull('a name is not an address; no place should have been created');
});

test('a warehouse created with a street still gets its place', function () {
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $request = Request::create('/pallet/int/v1/warehouses', 'POST', [
        'warehouse' => [
            'name'    => 'Addressed Warehouse',
            'code'    => 'ADDR-' . uniqid(),
            'street1' => '1 Logistics Way',
        ],
    ]);
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('company', $company);

    (new WarehouseController())->createRecord($request);

    $warehouse = Warehouse::where('name', 'Addressed Warehouse')->first();

    expect($warehouse)->not->toBeNull()
        ->and($warehouse->place_uuid)->not->toBeNull();
});
