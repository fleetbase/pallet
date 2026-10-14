<?php

use Fleetbase\Pallet\Http\Controllers\Api\v1\SalesOrderController;
use Fleetbase\Pallet\Http\Requests\CreateSalesOrderRequest;
use Fleetbase\Pallet\Http\Requests\UpdateSalesOrderRequest;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Support\Str;

/**
 * @return array{0:string,1:array,2:Product,3:Warehouse,4:Inventory}
 */
function soFixture(int $ordered = 10, int $onHand = 20): array
{
    $company = (string) Str::uuid();
    asCompany($company);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Fulfilment DC', 'code' => 'SO-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Sold Widget', 'sku' => 'SOW-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'product_uuid'   => $product->uuid,
        'quantity'       => $onHand,
        'status'         => 'active',
    ]);

    $request = publicApiRequest('sales-orders', 'POST', [
        'warehouse' => $warehouse->public_id,
        'items'     => [['product' => $product->public_id, 'quantity' => $ordered, 'unit_price' => 9]],
    ]);

    $body = resourceArray(
        (new SalesOrderController())->create(CreateSalesOrderRequest::createFrom($request)),
        $request
    );

    return [$company, $body, $product, $warehouse, $inventory->fresh()];
}

test('an order is created with its lines in one call', function () {
    [, $body, $product] = soFixture(10);

    expect($body['object'])->toBe('sales_order')
        ->and($body['id'])->toStartWith('sales_order_')
        ->and($body['status'])->toBe('pending')
        ->and($body['items'])->toHaveCount(1)
        ->and($body['items'][0]['product'])->toBe($product->public_id)
        ->and($body['items'][0]['quantity'])->toBe(10)
        ->and($body['items'][0]['outstanding_quantity'])->toBe(10);
});

test('the consumable order exposes no internal identifiers', function () {
    [, $body] = soFixture();

    foreach (['uuid', 'public_id', 'company_uuid', 'customer_uuid', 'customer_type', 'warehouse_uuid', 'created_by_uuid'] as $leaked) {
        expect(array_key_exists($leaked, $body))->toBeFalse($leaked . ' must not appear in the consumable representation');
    }

    foreach (['uuid', 'sales_order_uuid', 'product_uuid', 'inventory_uuid'] as $leaked) {
        expect(array_key_exists($leaked, $body['items'][0]))->toBeFalse($leaked . ' must not appear on a line');
    }
});

test('a partial fulfilment deducts exactly the quantity fulfilled', function () {
    [$company, $body, , , $inventory] = soFixture(10, 20);

    asCompany($company);
    $request   = publicApiRequest('sales-orders/' . $body['id'] . '/fulfill', 'POST', [
        'items' => [['id' => $body['items'][0]['id'], 'quantity_fulfilled' => 4]],
    ]);
    $fulfilled = resourceArray((new SalesOrderController())->fulfill($body['id'], $request), $request);

    expect($fulfilled['status'])->toBe('partial')
        ->and($fulfilled['items'][0]['quantity_fulfilled'])->toBe(4)
        ->and($fulfilled['items'][0]['outstanding_quantity'])->toBe(6)
        ->and((int) $inventory->fresh()->quantity)->toBe(16);
});

/*
 * The important one. A rejected fulfilment must leave stock exactly as it was —
 * a partial deduction on the way to failing is how inventory quietly goes wrong.
 */
test('insufficient stock leaves inventory completely unchanged', function () {
    [$company, $body, , , $inventory] = soFixture(50, 5);

    $before = (int) $inventory->fresh()->quantity;

    asCompany($company);
    $request  = publicApiRequest('sales-orders/' . $body['id'] . '/fulfill', 'POST', [
        'items' => [['id' => $body['items'][0]['id'], 'quantity_fulfilled' => 50]],
    ]);
    $response = (new SalesOrderController())->fulfill($body['id'], $request);

    expect($response->getStatusCode())->toBeGreaterThanOrEqual(400)
        ->and((int) $inventory->fresh()->quantity)->toBe($before)
        ->and(SalesOrder::where('public_id', $body['id'])->first()->status)->toBe('pending');
});

test('a fulfilment error speaks the consumable error envelope', function () {
    [$company, $body] = soFixture();

    asCompany($company);
    $request  = publicApiRequest('sales-orders/' . $body['id'] . '/fulfill', 'POST', ['items' => []]);
    $response = (new SalesOrderController())->fulfill($body['id'], $request);

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toHaveKey('error')
        ->and($response->getData(true))->not->toHaveKey('errors')
        ->and($response->getData(true)['error'])->toBe('No items provided for fulfillment.');
});

test('a fully fulfilled order cannot be fulfilled again', function () {
    [$company, $body, , , $inventory] = soFixture(5, 20);

    asCompany($company);
    $first = publicApiRequest('sales-orders/' . $body['id'] . '/fulfill', 'POST', [
        'items' => [['id' => $body['items'][0]['id'], 'quantity_fulfilled' => 5]],
    ]);
    (new SalesOrderController())->fulfill($body['id'], $first);

    $afterFirst = (int) $inventory->fresh()->quantity;

    $second   = publicApiRequest('sales-orders/' . $body['id'] . '/fulfill', 'POST', [
        'items' => [['id' => $body['items'][0]['id'], 'quantity_fulfilled' => 5]],
    ]);
    $response = (new SalesOrderController())->fulfill($body['id'], $second);

    expect($response->getStatusCode())->toBe(400)
        ->and($response->getData(true))->toHaveKey('error')
        ->and((int) $inventory->fresh()->quantity)->toBe($afterFirst);
});

test('a line whose product does not resolve fails the whole order', function () {
    $company = (string) Str::uuid();
    asCompany($company);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'DC', 'code' => 'SOX-' . uniqid()]);

    $request  = publicApiRequest('sales-orders', 'POST', [
        'warehouse' => $warehouse->public_id,
        'items'     => [['product' => 'product_does_not_exist', 'quantity' => 3]],
    ]);
    $response = (new SalesOrderController())->create(CreateSalesOrderRequest::createFrom($request));

    expect($response->getStatusCode())->toBe(404)
        ->and(SalesOrder::where('company_uuid', $company)->count())->toBe(0);
});

test('a warehouse belonging to another company does not resolve', function () {
    $companyA = (string) Str::uuid();
    $companyB = (string) Str::uuid();

    asCompany($companyB);
    $theirs = Warehouse::create(['company_uuid' => $companyB, 'name' => 'Theirs', 'code' => 'T-' . uniqid()]);

    asCompany($companyA);
    $request  = publicApiRequest('sales-orders', 'POST', ['warehouse' => $theirs->public_id]);
    $response = (new SalesOrderController())->create(CreateSalesOrderRequest::createFrom($request));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true)['error'])->toBe('Warehouse not found.');
});

test('an order listing returns only the calling company records', function () {
    [$companyA, $ours] = soFixture();
    soFixture();

    asCompany($companyA);
    $results = SalesOrder::queryWithRequest(publicApiRequest('sales-orders', 'GET', [], SalesOrderController::class));

    expect($results)->toHaveCount(1)
        ->and($results->first()->public_id)->toBe($ours['id']);
});

test('another company order is not found', function () {
    [, $theirs] = soFixture();

    asCompany((string) Str::uuid());
    $response = (new SalesOrderController())->find($theirs['id'], publicApiRequest('sales-orders/' . $theirs['id']));

    expect($response->getStatusCode())->toBe(404);
});

test('fulfilling against another company order is not found and moves no stock', function () {
    [, $theirs, , , $inventory] = soFixture(5, 20);

    $before = (int) $inventory->fresh()->quantity;

    asCompany((string) Str::uuid());
    $request  = publicApiRequest('sales-orders/' . $theirs['id'] . '/fulfill', 'POST', [
        'items' => [['id' => $theirs['items'][0]['id'], 'quantity_fulfilled' => 1]],
    ]);
    $response = (new SalesOrderController())->fulfill($theirs['id'], $request);

    expect($response->getStatusCode())->toBe(404)
        ->and((int) $inventory->fresh()->quantity)->toBe($before);
});

test('an update applies only the fields sent', function () {
    [$company, $body] = soFixture();

    asCompany($company);
    $request = publicApiRequest('sales-orders/' . $body['id'], 'PUT', ['reference_code' => 'SO-2001']);
    $updated = resourceArray(
        (new SalesOrderController())->update($body['id'], UpdateSalesOrderRequest::createFrom($request)),
        $request
    );

    expect($updated['reference_code'])->toBe('SO-2001')
        ->and($updated['status'])->toBe($body['status'])
        ->and($updated['items'])->toHaveCount(1);
});
