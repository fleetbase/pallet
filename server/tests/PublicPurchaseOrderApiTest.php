<?php

use Fleetbase\Pallet\Http\Controllers\Api\v1\PurchaseOrderController;
use Fleetbase\Pallet\Http\Requests\CreatePurchaseOrderRequest;
use Fleetbase\Pallet\Http\Requests\UpdatePurchaseOrderRequest;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\Supplier;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Support\Str;

function poFixture(int $quantity = 20): array
{
    $company = (string) Str::uuid();
    asCompany($company);

    $supplier  = Supplier::create(['company_uuid' => $company, 'name' => 'Acme Supply']);
    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Receiving DC', 'code' => 'PO-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Ordered Widget', 'sku' => 'POW-' . uniqid()]);

    $request = publicApiRequest('purchase-orders', 'POST', [
        'supplier'  => $supplier->public_id,
        'warehouse' => $warehouse->public_id,
        'items'     => [['product' => $product->public_id, 'quantity' => $quantity, 'unit_price' => 5]],
    ]);

    $body = resourceArray(
        (new PurchaseOrderController())->create(CreatePurchaseOrderRequest::createFrom($request)),
        $request
    );

    return [$company, $body, $product, $warehouse, $supplier];
}

test('an order is created with its lines in one call', function () {
    [, $body, $product] = poFixture(20);

    expect($body['object'])->toBe('purchase_order')
        ->and($body['id'])->toStartWith('purchase_order_')
        ->and($body['status'])->toBe('pending')
        ->and($body['items'])->toHaveCount(1)
        ->and($body['items'][0]['product'])->toBe($product->public_id)
        ->and($body['items'][0]['quantity'])->toBe(20)
        ->and($body['items'][0]['outstanding_quantity'])->toBe(20);
});

test('the consumable order exposes no internal identifiers', function () {
    [, $body] = poFixture();

    foreach (['uuid', 'public_id', 'company_uuid', 'supplier_uuid', 'warehouse_uuid', 'created_by_uuid'] as $leaked) {
        expect(array_key_exists($leaked, $body))->toBeFalse($leaked . ' must not appear in the consumable representation');
    }

    foreach (['uuid', 'purchase_order_uuid', 'product_uuid'] as $leaked) {
        expect(array_key_exists($leaked, $body['items'][0]))->toBeFalse($leaked . ' must not appear on a line');
    }
});

test('a partial receipt moves exactly the quantity received', function () {
    [$company, $body, $product, $warehouse] = poFixture(20);

    asCompany($company);
    $request = publicApiRequest('purchase-orders/' . $body['id'] . '/receive', 'POST', [
        'items' => [['id' => $body['items'][0]['id'], 'quantity_received' => 12]],
    ]);
    $received = resourceArray((new PurchaseOrderController())->receive($body['id'], $request), $request);

    expect($received['status'])->toBe('partial')
        ->and($received['items'][0]['quantity_received'])->toBe(12)
        ->and($received['items'][0]['outstanding_quantity'])->toBe(8);

    $inventory = Inventory::where('product_uuid', $product->uuid)->where('warehouse_uuid', $warehouse->uuid)->first();
    expect((int) $inventory->quantity)->toBe(12);
});

test('a receipt is capped at the outstanding quantity', function () {
    [$company, $body, $product, $warehouse] = poFixture(20);

    asCompany($company);
    $request = publicApiRequest('purchase-orders/' . $body['id'] . '/receive', 'POST', [
        'items' => [['id' => $body['items'][0]['id'], 'quantity_received' => 500]],
    ]);
    $received = resourceArray((new PurchaseOrderController())->receive($body['id'], $request), $request);

    expect($received['status'])->toBe('received')
        ->and($received['items'][0]['quantity_received'])->toBe(20)
        ->and($received['items'][0]['outstanding_quantity'])->toBe(0);

    $inventory = Inventory::where('product_uuid', $product->uuid)->where('warehouse_uuid', $warehouse->uuid)->first();
    expect((int) $inventory->quantity)->toBe(20);
});

test('a fully received order cannot be received again', function () {
    [$company, $body] = poFixture(5);

    asCompany($company);
    $first = publicApiRequest('purchase-orders/' . $body['id'] . '/receive', 'POST', [
        'items' => [['id' => $body['items'][0]['id'], 'quantity_received' => 5]],
    ]);
    (new PurchaseOrderController())->receive($body['id'], $first);

    $second   = publicApiRequest('purchase-orders/' . $body['id'] . '/receive', 'POST', [
        'items' => [['id' => $body['items'][0]['id'], 'quantity_received' => 5]],
    ]);
    $response = (new PurchaseOrderController())->receive($body['id'], $second);

    expect($response->getStatusCode())->toBe(400)
        ->and($response->getData(true))->toHaveKey('error')
        ->and($response->getData(true))->not->toHaveKey('errors');
});

test('a receipt error speaks the consumable error envelope', function () {
    [$company, $body] = poFixture(5);

    asCompany($company);
    $request  = publicApiRequest('purchase-orders/' . $body['id'] . '/receive', 'POST', ['items' => []]);
    $response = (new PurchaseOrderController())->receive($body['id'], $request);

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toHaveKey('error')
        ->and($response->getData(true)['error'])->toBe('No items provided for receipt.');
});

test('a line whose product does not resolve fails the whole order', function () {
    $company = (string) Str::uuid();
    asCompany($company);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'DC', 'code' => 'X-' . uniqid()]);

    $request  = publicApiRequest('purchase-orders', 'POST', [
        'warehouse' => $warehouse->public_id,
        'items'     => [['product' => 'product_does_not_exist', 'quantity' => 3]],
    ]);
    $response = (new PurchaseOrderController())->create(CreatePurchaseOrderRequest::createFrom($request));

    expect($response->getStatusCode())->toBe(404)
        ->and(PurchaseOrder::where('company_uuid', $company)->count())->toBe(0);
});

test('a supplier belonging to another company does not resolve', function () {
    $companyA = (string) Str::uuid();
    $companyB = (string) Str::uuid();

    asCompany($companyB);
    $theirSupplier = Supplier::create(['company_uuid' => $companyB, 'name' => 'Theirs']);

    asCompany($companyA);
    $request  = publicApiRequest('purchase-orders', 'POST', ['supplier' => $theirSupplier->public_id]);
    $response = (new PurchaseOrderController())->create(CreatePurchaseOrderRequest::createFrom($request));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true)['error'])->toBe('Supplier not found.');
});

test('an order listing returns only the calling company records', function () {
    [$companyA, $ours] = poFixture();
    poFixture();

    asCompany($companyA);
    $results = PurchaseOrder::queryWithRequest(publicApiRequest('purchase-orders', 'GET', [], PurchaseOrderController::class));

    expect($results)->toHaveCount(1)
        ->and($results->first()->public_id)->toBe($ours['id']);
});

test('another company order is not found', function () {
    [, $theirs] = poFixture();

    asCompany((string) Str::uuid());
    $response = (new PurchaseOrderController())->find($theirs['id'], publicApiRequest('purchase-orders/' . $theirs['id']));

    expect($response->getStatusCode())->toBe(404);
});

test('receiving against another company order is not found', function () {
    [, $theirs] = poFixture();

    asCompany((string) Str::uuid());
    $request  = publicApiRequest('purchase-orders/' . $theirs['id'] . '/receive', 'POST', [
        'items' => [['id' => $theirs['items'][0]['id'], 'quantity_received' => 1]],
    ]);
    $response = (new PurchaseOrderController())->receive($theirs['id'], $request);

    expect($response->getStatusCode())->toBe(404);
});

test('an update applies only the fields sent', function () {
    [$company, $body] = poFixture();

    asCompany($company);
    $request = publicApiRequest('purchase-orders/' . $body['id'], 'PUT', ['reference_code' => 'PO-1001']);
    $updated = resourceArray(
        (new PurchaseOrderController())->update($body['id'], UpdatePurchaseOrderRequest::createFrom($request)),
        $request
    );

    expect($updated['reference_code'])->toBe('PO-1001')
        ->and($updated['status'])->toBe($body['status'])
        ->and($updated['items'])->toHaveCount(1);
});
