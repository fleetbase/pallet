<?php

use Fleetbase\Pallet\Http\Controllers\Api\v1\AuditController;
use Fleetbase\Pallet\Http\Controllers\Api\v1\BatchController;
use Fleetbase\Pallet\Http\Controllers\Api\v1\StockAdjustmentController;
use Fleetbase\Pallet\Http\Controllers\Api\v1\SupplierController;
use Fleetbase\Pallet\Http\Requests\CreateStockAdjustmentRequest;
use Fleetbase\Pallet\Http\Requests\CreateSupplierRequest;
use Fleetbase\Pallet\Http\Requests\UpdateSupplierRequest;
use Fleetbase\Pallet\Models\Batch;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\StockAdjustment;
use Fleetbase\Pallet\Models\Supplier;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Support\Str;

function publicAdjustmentFixture(int $onHand = 20): array
{
    $company = (string) Str::uuid();
    asCompany($company);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Adj DC', 'code' => 'ADJ-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Adjusted', 'sku' => 'ADJ-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'product_uuid'   => $product->uuid,
        'quantity'       => $onHand,
        'status'         => 'active',
    ]);

    return [$company, $product, $warehouse, $inventory->fresh()];
}

function submitAdjustment(string $type, int $quantity, Product $product, Warehouse $warehouse)
{
    $request = publicApiRequest('stock-adjustments', 'POST', [
        'product'   => $product->public_id,
        'warehouse' => $warehouse->public_id,
        'type'      => $type,
        'quantity'  => $quantity,
        'reason'    => 'contract test',
    ]);

    return [(new StockAdjustmentController())->create(CreateStockAdjustmentRequest::createFrom($request)), $request];
}

/* ---------------------------------------------------------------- adjustments */

test('an add adjustment raises stock and reports both sides of the move', function () {
    [, $product, $warehouse, $inventory] = publicAdjustmentFixture(20);

    [$resource, $request] = submitAdjustment('add', 5, $product, $warehouse);
    $body                 = resourceArray($resource, $request);

    expect($body['object'])->toBe('stock_adjustment')
        ->and($body['before_quantity'])->toBe(20)
        ->and($body['after_quantity'])->toBe(25)
        ->and($body['delta'])->toBe(5)
        ->and($body['product'])->toBe($product->public_id)
        ->and((int) $inventory->fresh()->quantity)->toBe(25);
});

test('a remove adjustment lowers stock and reports a negative delta', function () {
    [, $product, $warehouse, $inventory] = publicAdjustmentFixture(20);

    [$resource, $request] = submitAdjustment('remove', 8, $product, $warehouse);
    $body                 = resourceArray($resource, $request);

    expect($body['after_quantity'])->toBe(12)
        ->and($body['delta'])->toBe(-8)
        ->and((int) $inventory->fresh()->quantity)->toBe(12);
});

test('the consumable adjustment exposes no internal identifiers', function () {
    [, $product, $warehouse] = publicAdjustmentFixture();

    [$resource, $request] = submitAdjustment('add', 1, $product, $warehouse);
    $body                 = resourceArray($resource, $request);

    foreach (['uuid', 'public_id', 'company_uuid', 'product_uuid', 'warehouse_uuid', 'inventory_uuid', 'created_by_uuid'] as $leaked) {
        expect(array_key_exists($leaked, $body))->toBeFalse($leaked . ' must not appear in the consumable representation');
    }
});

test('an adjustment error speaks the consumable error envelope', function () {
    [, , $warehouse] = publicAdjustmentFixture();

    $request = publicApiRequest('stock-adjustments', 'POST', [
        'product'   => 'product_does_not_exist',
        'warehouse' => $warehouse->public_id,
        'type'      => 'add',
        'quantity'  => 1,
    ]);
    $response = (new StockAdjustmentController())->create(CreateStockAdjustmentRequest::createFrom($request));

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toHaveKey('error')
        ->and($response->getData(true))->not->toHaveKey('errors');
});

test('the consumable API offers no way to rewrite or erase an adjustment', function () {
    foreach (['update', 'delete'] as $method) {
        expect(method_exists(StockAdjustmentController::class, $method))->toBeFalse(
            'an adjustment records something that happened — correcting it means making another, not a ' . $method
        );
    }
});

test('an adjustment listing returns only the calling company records', function () {
    [$companyA, $productA, $warehouseA] = publicAdjustmentFixture();
    submitAdjustment('add', 2, $productA, $warehouseA);

    [, $productB, $warehouseB] = publicAdjustmentFixture();
    submitAdjustment('add', 2, $productB, $warehouseB);

    asCompany($companyA);
    $results = StockAdjustment::queryWithRequest(
        publicApiRequest('stock-adjustments', 'GET', [], StockAdjustmentController::class)
    );

    expect($results)->toHaveCount(1)
        ->and($results->first()->product_uuid)->toBe($productA->uuid);
});

/* ------------------------------------------------------------------ suppliers */

test('a supplier round-trips through the consumable API', function () {
    $company = (string) Str::uuid();
    asCompany($company);

    $create = publicApiRequest('suppliers', 'POST', ['name' => 'Acme Supply', 'email' => 'ops@acme.test']);
    $body   = resourceArray(
        (new SupplierController())->create(CreateSupplierRequest::createFrom($create)),
        $create
    );

    expect($body['object'])->toBe('supplier')
        ->and($body['id'])->toStartWith('supplier_')
        ->and($body['name'])->toBe('Acme Supply');

    $update  = publicApiRequest('suppliers/' . $body['id'], 'PUT', ['phone' => '+65 1234 5678']);
    $updated = resourceArray(
        (new SupplierController())->update($body['id'], UpdateSupplierRequest::createFrom($update)),
        $update
    );

    expect($updated['phone'])->toBe('+65 1234 5678')
        ->and($updated['name'])->toBe('Acme Supply');
});

test('a supplier listing excludes other companies and non-pallet vendors', function () {
    $companyA = (string) Str::uuid();
    $companyB = (string) Str::uuid();

    asCompany($companyA);
    $ours = Supplier::create(['company_uuid' => $companyA, 'name' => 'Ours']);

    asCompany($companyB);
    Supplier::create(['company_uuid' => $companyB, 'name' => 'Theirs']);

    asCompany($companyA);
    Fleetbase\FleetOps\Models\Vendor::create(['company_uuid' => $companyA, 'name' => 'A FleetOps Vendor']);

    $results = Supplier::queryWithRequest(publicApiRequest('suppliers', 'GET', [], SupplierController::class));

    expect($results)->toHaveCount(1)
        ->and($results->first()->public_id)->toBe($ours->public_id);
});

test('another company supplier is not found', function () {
    $companyB = (string) Str::uuid();
    asCompany($companyB);
    $theirs = Supplier::create(['company_uuid' => $companyB, 'name' => 'Theirs']);

    asCompany((string) Str::uuid());
    $response = (new SupplierController())->find($theirs->public_id, publicApiRequest('suppliers/' . $theirs->public_id));

    expect($response->getStatusCode())->toBe(404);
});

/* -------------------------------------------------------------------- batches */

test('a batch reports the quantity it arrived with', function () {
    $company = (string) Str::uuid();
    asCompany($company);

    $product = Product::create(['company_uuid' => $company, 'name' => 'Lotted', 'sku' => 'LOT-' . uniqid()]);
    $batch   = Batch::create([
        'company_uuid' => $company,
        'product_uuid' => $product->uuid,
        'batch_number' => 'B-1001',
        'quantity'     => 40,
    ]);

    $request = publicApiRequest('batches/' . $batch->public_id);
    $body    = resourceArray((new BatchController())->find($batch->public_id, $request), $request);

    expect($body['object'])->toBe('batch')
        ->and($body['batch_number'])->toBe('B-1001')
        ->and($body['received_quantity'])->toBe(40)
        ->and($body['product'])->toBe($product->public_id)
        ->and(array_key_exists('company_uuid', $body))->toBeFalse();
});

test('the consumable API offers no way to author a batch or an audit entry', function () {
    foreach ([BatchController::class, AuditController::class] as $controller) {
        foreach (['create', 'update', 'delete'] as $method) {
            expect(method_exists($controller, $method))->toBeFalse(
                class_basename($controller) . ' must not expose ' . $method . ' — these records describe what the system did'
            );
        }
    }
});

/* --------------------------------------------------------------------- audits */

test('an audit entry names its subject by public id and short type', function () {
    [, $product, $warehouse] = publicAdjustmentFixture();
    submitAdjustment('add', 3, $product, $warehouse);

    $entries = Fleetbase\Pallet\Models\Audit::queryWithRequest(
        publicApiRequest('audits', 'GET', [], AuditController::class)
    );

    expect($entries)->not->toBeEmpty();

    $request = publicApiRequest('audits/' . $entries->first()->public_id);
    $body    = resourceArray((new AuditController())->find($entries->first()->public_id, $request), $request);

    expect($body['object'])->toBe('audit')
        ->and($body['event_type'])->not->toBeNull()
        ->and($body['subject_type'])->not->toContain('\\')
        ->and(array_key_exists('auditable_uuid', $body))->toBeFalse()
        ->and(array_key_exists('auditable_type', $body))->toBeFalse();
});
