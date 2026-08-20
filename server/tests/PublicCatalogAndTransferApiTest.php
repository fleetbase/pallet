<?php

use Fleetbase\Pallet\Http\Controllers\Api\v1\BinLocationController;
use Fleetbase\Pallet\Http\Controllers\Api\v1\ProductVariantController;
use Fleetbase\Pallet\Http\Controllers\Api\v1\StockTransferController;
use Fleetbase\Pallet\Http\Controllers\Api\v1\WarehouseZoneController;
use Fleetbase\Pallet\Http\Requests\CreateBinLocationRequest;
use Fleetbase\Pallet\Http\Requests\CreateProductVariantRequest;
use Fleetbase\Pallet\Http\Requests\CreateStockTransferRequest;
use Fleetbase\Pallet\Http\Requests\CreateWarehouseZoneRequest;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\StockTransfer;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Support\Str;

/* ------------------------------------------------------------------- variants */

test('a variant is created against a product addressed by public id', function () {
    $company = (string) Str::uuid();
    asCompany($company);
    $product = Product::create(['company_uuid' => $company, 'name' => 'Shirt', 'sku' => 'SH-' . uniqid()]);

    $request = publicApiRequest('product-variants', 'POST', [
        'product'       => $product->public_id,
        'name'          => 'Large / Blue',
        'sku'           => 'SH-L-BLU',
        'option_values' => ['size' => 'L', 'color' => 'blue'],
    ]);
    $body = resourceArray(
        (new ProductVariantController())->create(CreateProductVariantRequest::createFrom($request)),
        $request
    );

    expect($body['object'])->toBe('product_variant')
        ->and($body['id'])->toStartWith('variant_')
        ->and($body['product'])->toBe($product->public_id)
        ->and($body['name'])->toBe('Large / Blue')
        ->and(array_key_exists('product_uuid', $body))->toBeFalse();
});

test('a variant cannot be created against another company product', function () {
    $companyB = (string) Str::uuid();
    asCompany($companyB);
    $theirs = Product::create(['company_uuid' => $companyB, 'name' => 'Theirs', 'sku' => 'T-' . uniqid()]);

    asCompany((string) Str::uuid());
    $request  = publicApiRequest('product-variants', 'POST', ['product' => $theirs->public_id, 'name' => 'X']);
    $response = (new ProductVariantController())->create(CreateProductVariantRequest::createFrom($request));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true)['error'])->toBe('Product not found.');
});

/* ---------------------------------------------------------------------- zones */

test('a zone is created against a warehouse addressed by public id', function () {
    $company = (string) Str::uuid();
    asCompany($company);
    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'DC', 'code' => 'Z-' . uniqid()]);

    $request = publicApiRequest('warehouse-zones', 'POST', [
        'warehouse' => $warehouse->public_id,
        'name'      => 'Receiving',
        'code'      => 'RCV',
    ]);
    $body = resourceArray(
        (new WarehouseZoneController())->create(CreateWarehouseZoneRequest::createFrom($request)),
        $request
    );

    expect($body['object'])->toBe('warehouse_zone')
        ->and($body['id'])->toStartWith('zone_')
        ->and($body['warehouse'])->toBe($warehouse->public_id)
        ->and($body['code'])->toBe('RCV')
        ->and(array_key_exists('warehouse_uuid', $body))->toBeFalse();
});

/* ------------------------------------------------------------- bin locations */

test('a bin is created in a warehouse and its zone', function () {
    $company = (string) Str::uuid();
    asCompany($company);
    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'DC', 'code' => 'B-' . uniqid()]);

    $zoneRequest = publicApiRequest('warehouse-zones', 'POST', ['warehouse' => $warehouse->public_id, 'name' => 'Bulk']);
    $zone        = resourceArray(
        (new WarehouseZoneController())->create(CreateWarehouseZoneRequest::createFrom($zoneRequest)),
        $zoneRequest
    );

    $request = publicApiRequest('bin-locations', 'POST', [
        'warehouse'  => $warehouse->public_id,
        'zone'       => $zone['id'],
        'bin_number' => 'A-01-01',
    ]);
    $body = resourceArray(
        (new BinLocationController())->create(CreateBinLocationRequest::createFrom($request)),
        $request
    );

    expect($body['object'])->toBe('bin_location')
        ->and($body['id'])->toStartWith('bin_')
        ->and($body['bin_number'])->toBe('A-01-01')
        ->and($body['warehouse'])->toBe($warehouse->public_id)
        ->and($body['zone'])->toBe($zone['id']);
});

/*
 * A bin placed in a zone belonging to a different warehouse would claim a position
 * in two buildings at once.
 */
test('a bin cannot be placed in a zone from another warehouse', function () {
    $company = (string) Str::uuid();
    asCompany($company);

    $warehouseA = Warehouse::create(['company_uuid' => $company, 'name' => 'A', 'code' => 'BA-' . uniqid()]);
    $warehouseB = Warehouse::create(['company_uuid' => $company, 'name' => 'B', 'code' => 'BB-' . uniqid()]);

    $zoneRequest = publicApiRequest('warehouse-zones', 'POST', ['warehouse' => $warehouseB->public_id, 'name' => 'Elsewhere']);
    $zone        = resourceArray(
        (new WarehouseZoneController())->create(CreateWarehouseZoneRequest::createFrom($zoneRequest)),
        $zoneRequest
    );

    $request  = publicApiRequest('bin-locations', 'POST', [
        'warehouse'  => $warehouseA->public_id,
        'zone'       => $zone['id'],
        'bin_number' => 'A-01-01',
    ]);
    $response = (new BinLocationController())->create(CreateBinLocationRequest::createFrom($request));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true)['error'])->toBe('Zone not found in this warehouse.');
});

/* ------------------------------------------------------------------ transfers */

function transferFixture(int $onHand = 30): array
{
    $company = (string) Str::uuid();
    asCompany($company);

    $from    = Warehouse::create(['company_uuid' => $company, 'name' => 'Source', 'code' => 'FR-' . uniqid()]);
    $to      = Warehouse::create(['company_uuid' => $company, 'name' => 'Destination', 'code' => 'TO-' . uniqid()]);
    $product = Product::create(['company_uuid' => $company, 'name' => 'Moved', 'sku' => 'MV-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $from->uuid,
        'product_uuid'   => $product->uuid,
        'quantity'       => $onHand,
        'status'         => 'active',
    ]);

    $request = publicApiRequest('stock-transfers', 'POST', [
        'from_warehouse' => $from->public_id,
        'to_warehouse'   => $to->public_id,
        'items'          => [['product' => $product->public_id, 'quantity' => 10]],
    ]);
    $body = resourceArray(
        (new StockTransferController())->create(CreateStockTransferRequest::createFrom($request)),
        $request
    );

    return [$company, $body, $product, $from, $to, $inventory->fresh()];
}

test('a transfer is created pending with its lines', function () {
    [, $body, $product, $from, $to] = transferFixture();

    expect($body['object'])->toBe('stock_transfer')
        ->and($body['id'])->toStartWith('transfer_')
        ->and($body['status'])->toBe('pending')
        ->and($body['from_warehouse'])->toBe($from->public_id)
        ->and($body['to_warehouse'])->toBe($to->public_id)
        ->and($body['items'])->toHaveCount(1)
        ->and($body['items'][0]['product'])->toBe($product->public_id)
        ->and($body['items'][0]['quantity'])->toBe(10);
});

test('a transfer between the same warehouse is rejected', function () {
    $company = (string) Str::uuid();
    asCompany($company);
    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Only', 'code' => 'ON-' . uniqid()]);

    $request  = publicApiRequest('stock-transfers', 'POST', [
        'from_warehouse' => $warehouse->public_id,
        'to_warehouse'   => $warehouse->public_id,
    ]);
    $response = (new StockTransferController())->create(CreateStockTransferRequest::createFrom($request));

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true)['error'])->toBe('Source and destination warehouses must be different.');
});

/*
 * The reason the lifecycle is transitions rather than a settable status field:
 * cancelling an in-transit transfer has to give the stock back.
 */
test('cancelling an in-transit transfer restores the shipped stock', function () {
    [$company, $body, , , , $inventory] = transferFixture(30);

    asCompany($company);
    (new StockTransferController())->approve($body['id'], publicApiRequest('stock-transfers/' . $body['id'] . '/approve', 'POST'));
    (new StockTransferController())->ship($body['id'], publicApiRequest('stock-transfers/' . $body['id'] . '/ship', 'POST'));

    $afterShip = (int) $inventory->fresh()->quantity;
    expect($afterShip)->toBe(20);

    (new StockTransferController())->cancel($body['id'], publicApiRequest('stock-transfers/' . $body['id'] . '/cancel', 'POST'));

    expect((int) $inventory->fresh()->quantity)->toBe(30)
        ->and(StockTransfer::where('public_id', $body['id'])->first()->status)->toBe('cancelled');
});

test('a rejected transition surfaces the reason and does not change status', function () {
    [$company, $body] = transferFixture();

    asCompany($company);
    $response = (new StockTransferController())->ship($body['id'], publicApiRequest('stock-transfers/' . $body['id'] . '/ship', 'POST'));

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toHaveKey('error')
        ->and(StockTransfer::where('public_id', $body['id'])->first()->status)->toBe('pending');
});

test('the consumable API offers no way to set a transfer status directly', function () {
    expect(method_exists(StockTransferController::class, 'update'))->toBeFalse(
        'a settable status would move the record without moving the stock'
    );
});

test('another company transfer is not found', function () {
    [, $theirs] = transferFixture();

    asCompany((string) Str::uuid());
    $response = (new StockTransferController())->find($theirs['id'], publicApiRequest('stock-transfers/' . $theirs['id']));

    expect($response->getStatusCode())->toBe(404);
});
