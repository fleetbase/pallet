<?php

use Fleetbase\Pallet\Http\Controllers\Api\v1\WarehouseController;
use Fleetbase\Pallet\Http\Requests\CreateWarehouseRequest;
use Fleetbase\Pallet\Http\Requests\UpdateWarehouseRequest;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Support\Str;

function makePublicWarehouse(string $company, string $name): Warehouse
{
    asCompany($company);

    return Warehouse::create([
        'company_uuid' => $company,
        'name'         => $name,
        'code'         => 'WH-' . uniqid(),
    ]);
}

test('a warehouse is addressed and returned by its public id', function () {
    $company   = (string) Str::uuid();
    $warehouse = makePublicWarehouse($company, 'Singapore DC');

    $request = publicApiRequest('warehouses/' . $warehouse->public_id);
    $body    = resourceArray((new WarehouseController())->find($warehouse->public_id, $request), $request);

    expect($body['id'])->toBe($warehouse->public_id)
        ->and($body['object'])->toBe('warehouse')
        ->and($body['name'])->toBe('Singapore DC');
});

test('the consumable warehouse exposes no internal identifiers', function () {
    $company   = (string) Str::uuid();
    $warehouse = makePublicWarehouse($company, 'Opaque DC');

    $request = publicApiRequest('warehouses/' . $warehouse->public_id);
    $body    = resourceArray((new WarehouseController())->find($warehouse->public_id, $request), $request);

    foreach (['uuid', 'public_id', 'company_uuid', 'created_by_uuid', 'place_uuid', 'manager_uuid'] as $leaked) {
        expect(array_key_exists($leaked, $body))->toBeFalse($leaked . ' must not appear in the consumable representation');
    }

    expect(json_encode($body))->not->toContain($warehouse->uuid);
});

test('a warehouse listing returns only the calling company records', function () {
    $companyA = (string) Str::uuid();
    $companyB = (string) Str::uuid();

    $ours = makePublicWarehouse($companyA, 'Ours');
    makePublicWarehouse($companyB, 'Theirs');

    asCompany($companyA);
    $results = Warehouse::queryWithRequest(publicApiRequest('warehouses', 'GET', [], WarehouseController::class));

    expect($results)->toHaveCount(1)
        ->and($results->first()->public_id)->toBe($ours->public_id);
});

test('another company warehouse is not found', function () {
    $companyA = (string) Str::uuid();
    $companyB = (string) Str::uuid();

    $theirs = makePublicWarehouse($companyB, 'Theirs');

    asCompany($companyA);
    $response = (new WarehouseController())->find($theirs->public_id, publicApiRequest('warehouses/' . $theirs->public_id));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true)['error'])->toBe('Warehouse resource not found.');
});

test('a warehouse is created against the calling company', function () {
    $company = (string) Str::uuid();
    asCompany($company);

    $request = publicApiRequest('warehouses', 'POST', ['name' => 'New DC', 'code' => 'NDC']);
    $body    = resourceArray(
        (new WarehouseController())->create(CreateWarehouseRequest::createFrom($request)),
        $request
    );

    expect($body['name'])->toBe('New DC')
        ->and(Warehouse::where('public_id', $body['id'])->first()->company_uuid)->toBe($company);
});

test('a consumer cannot assign a warehouse to another company', function () {
    $company = (string) Str::uuid();
    asCompany($company);

    $request = publicApiRequest('warehouses', 'POST', ['name' => 'Smuggled', 'company_uuid' => (string) Str::uuid()]);
    $body    = resourceArray(
        (new WarehouseController())->create(CreateWarehouseRequest::createFrom($request)),
        $request
    );

    expect(Warehouse::where('public_id', $body['id'])->first()->company_uuid)->toBe($company);
});

test('an update applies only the fields sent', function () {
    $company   = (string) Str::uuid();
    $warehouse = makePublicWarehouse($company, 'Before');
    $code      = $warehouse->code;

    $request = publicApiRequest('warehouses/' . $warehouse->public_id, 'PUT', ['name' => 'After']);
    $body    = resourceArray(
        (new WarehouseController())->update($warehouse->public_id, UpdateWarehouseRequest::createFrom($request)),
        $request
    );

    expect($body['name'])->toBe('After')
        ->and($body['code'])->toBe($code);
});

test('a deleted warehouse is confirmed and removed', function () {
    $company   = (string) Str::uuid();
    $warehouse = makePublicWarehouse($company, 'Doomed');

    $request = publicApiRequest('warehouses/' . $warehouse->public_id, 'DELETE');
    $body    = resourceArray((new WarehouseController())->delete($warehouse->public_id, $request), $request);

    expect($body['id'])->toBe($warehouse->public_id)
        ->and($body['deleted'])->toBeTrue()
        ->and(Warehouse::where('public_id', $warehouse->public_id)->first())->toBeNull();
});

test('a warehouse reports how many stock items it holds', function () {
    $company   = (string) Str::uuid();
    $warehouse = makePublicWarehouse($company, 'Counted DC');
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Counted', 'sku' => 'CNT-' . uniqid()]);

    Inventory::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'product_uuid'   => $product->uuid,
        'quantity'       => 5,
        'status'         => 'active',
    ]);

    $request = publicApiRequest('warehouses/' . $warehouse->public_id);
    $body    = resourceArray((new WarehouseController())->find($warehouse->public_id, $request), $request);

    expect($body['stock_item_count'])->toBe(1);
});
