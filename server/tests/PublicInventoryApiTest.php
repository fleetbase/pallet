<?php

use Fleetbase\Pallet\Http\Controllers\Api\v1\InventoryController;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Support\Str;

function seedPublicStock(string $company, string $warehouseName, Product $product, int $quantity, int $reserved = 0): array
{
    asCompany($company);

    $warehouse = Warehouse::create([
        'company_uuid' => $company,
        'name'         => $warehouseName,
        'code'         => 'INV-' . uniqid(),
    ]);

    $inventory = Inventory::create([
        'company_uuid'      => $company,
        'warehouse_uuid'    => $warehouse->uuid,
        'product_uuid'      => $product->uuid,
        'quantity'          => $quantity,
        'reserved_quantity' => $reserved,
        'status'            => 'active',
    ]);

    return [$warehouse, $inventory->fresh()];
}

function makeStockedProduct(string $company, int $quantity = 20, int $reserved = 0): array
{
    asCompany($company);
    $product = Product::create(['company_uuid' => $company, 'name' => 'Stocked', 'sku' => 'STK-' . uniqid()]);

    [$warehouse, $inventory] = seedPublicStock($company, 'Main DC', $product, $quantity, $reserved);

    return [$product, $warehouse, $inventory];
}

test('a stock record reports on-hand, reserved and available together', function () {
    $company                        = (string) Str::uuid();
    [$product, $warehouse, $record] = makeStockedProduct($company, 20, 6);

    $request = publicApiRequest('inventory/' . $record->public_id);
    $body    = resourceArray((new InventoryController())->find($record->public_id, $request), $request);

    expect($body['object'])->toBe('inventory')
        ->and($body['quantity'])->toBe(20)
        ->and($body['reserved_quantity'])->toBe(6)
        ->and($body['available_quantity'])->toBe(14)
        ->and($body['product'])->toBe($product->public_id)
        ->and($body['warehouse'])->toBe($warehouse->public_id)
        ->and($body['sku'])->toBe($product->sku);
});

test('the consumable stock record exposes no internal identifiers', function () {
    $company      = (string) Str::uuid();
    [, , $record] = makeStockedProduct($company);

    $request = publicApiRequest('inventory/' . $record->public_id);
    $body    = resourceArray((new InventoryController())->find($record->public_id, $request), $request);

    foreach (['uuid', 'public_id', 'company_uuid', 'product_uuid', 'warehouse_uuid', 'batch_uuid', 'variant_uuid'] as $leaked) {
        expect(array_key_exists($leaked, $body))->toBeFalse($leaked . ' must not appear in the consumable representation');
    }

    expect(json_encode($body))->not->toContain($record->uuid);
});

test('a stock listing returns only the calling company records', function () {
    $companyA = (string) Str::uuid();
    $companyB = (string) Str::uuid();

    [, , $ours] = makeStockedProduct($companyA);
    makeStockedProduct($companyB);

    asCompany($companyA);
    $results = Inventory::queryWithRequest(publicApiRequest('inventory', 'GET', [], InventoryController::class));

    expect($results)->toHaveCount(1)
        ->and($results->first()->public_id)->toBe($ours->public_id);
});

test('another company stock record is not found', function () {
    $companyA     = (string) Str::uuid();
    $companyB     = (string) Str::uuid();
    [, , $theirs] = makeStockedProduct($companyB);

    asCompany($companyA);
    $response = (new InventoryController())->find($theirs->public_id, publicApiRequest('inventory/' . $theirs->public_id));

    expect($response->getStatusCode())->toBe(404);
});

test('availability answers whether the requested quantity can be met', function () {
    $company           = (string) Str::uuid();
    [$product]         = makeStockedProduct($company, 20, 6);

    $request  = publicApiRequest('inventory/availability', 'GET', ['product' => $product->public_id, 'quantity' => 10]);
    $body     = (new InventoryController())->availability($request)->getData(true);

    expect($body['available'])->toBeTrue()
        ->and($body['available_quantity'])->toBe(14)
        ->and($body['reserved_quantity'])->toBe(6)
        ->and($body['quantity'])->toBe(20)
        ->and($body['shortage_quantity'])->toBe(0)
        ->and($body['out_of_stock'])->toBeFalse();
});

test('availability reports the shortage when the request cannot be met', function () {
    $company   = (string) Str::uuid();
    [$product] = makeStockedProduct($company, 20, 18);

    $request = publicApiRequest('inventory/availability', 'GET', ['product' => $product->public_id, 'quantity' => 10]);
    $body    = (new InventoryController())->availability($request)->getData(true);

    expect($body['available'])->toBeFalse()
        ->and($body['available_quantity'])->toBe(2)
        ->and($body['shortage_quantity'])->toBe(8);
});

test('availability aggregates across warehouses and breaks the total down by each', function () {
    $company   = (string) Str::uuid();
    [$product] = makeStockedProduct($company, 20, 0);
    seedPublicStock($company, 'Second DC', $product, 15, 5);

    $request = publicApiRequest('inventory/availability', 'GET', ['product' => $product->public_id, 'quantity' => 30]);
    $body    = (new InventoryController())->availability($request)->getData(true);

    expect($body['available_quantity'])->toBe(30)
        ->and($body['available'])->toBeTrue()
        ->and($body['by_warehouse'])->toHaveCount(2)
        ->and(array_sum(array_column($body['by_warehouse'], 'available_quantity')))->toBe(30);
});

test('availability can be narrowed to a single warehouse', function () {
    $company              = (string) Str::uuid();
    [$product, $first]    = makeStockedProduct($company, 20, 0);
    seedPublicStock($company, 'Second DC', $product, 15, 0);

    $request = publicApiRequest('inventory/availability', 'GET', [
        'product'   => $product->public_id,
        'warehouse' => $first->public_id,
        'quantity'  => 1,
    ]);
    $body = (new InventoryController())->availability($request)->getData(true);

    expect($body['warehouse'])->toBe($first->public_id)
        ->and($body['available_quantity'])->toBe(20)
        ->and($body['by_warehouse'])->toHaveCount(1);
});

test('availability accepts a sku as well as a public id', function () {
    $company   = (string) Str::uuid();
    [$product] = makeStockedProduct($company, 7, 0);

    $request = publicApiRequest('inventory/availability', 'GET', ['sku' => $product->sku]);
    $body    = (new InventoryController())->availability($request)->getData(true);

    expect($body['product'])->toBe($product->public_id)
        ->and($body['available_quantity'])->toBe(7);
});

test('availability for another company product is not found', function () {
    $companyA  = (string) Str::uuid();
    $companyB  = (string) Str::uuid();
    [$theirs]  = makeStockedProduct($companyB);

    asCompany($companyA);
    $response = (new InventoryController())->availability(
        publicApiRequest('inventory/availability', 'GET', ['product' => $theirs->public_id])
    );

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true)['error'])->toBe('Product not found.');
});

test('availability for another company warehouse is not found', function () {
    $companyA  = (string) Str::uuid();
    $companyB  = (string) Str::uuid();
    [, $their] = makeStockedProduct($companyB);
    [$ours]    = makeStockedProduct($companyA);

    asCompany($companyA);
    $response = (new InventoryController())->availability(
        publicApiRequest('inventory/availability', 'GET', ['product' => $ours->public_id, 'warehouse' => $their->public_id])
    );

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true)['error'])->toBe('Warehouse not found.');
});

test('the consumable API offers no way to write a stock level directly', function () {
    foreach (['create', 'update', 'delete'] as $method) {
        expect(method_exists(InventoryController::class, $method))->toBeFalse(
            'stock levels must follow from receipts, fulfilments, transfers and adjustments — not from a direct ' . $method
        );
    }
});
