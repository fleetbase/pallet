<?php

use Fleetbase\Pallet\Http\Controllers\PurchaseOrderController;
use Fleetbase\Pallet\Http\Controllers\SalesOrderController;
use Fleetbase\Pallet\Models\Audit;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\PurchaseOrderItem;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\SalesOrderItem;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;

/**
 * Partial receipts and partial fulfillments move stock, so they must leave an
 * audit trail — previously only fully received/fulfilled orders logged an event
 * and every partial stock movement went unrecorded.
 */
function makePartialAuditFixture(int $quantity): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Audit WH', 'code' => 'AWH-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Audit Product', 'sku' => 'AUD-' . uniqid()]);

    return [$company, $warehouse, $product];
}

test('a partial receipt logs an audit event carrying the received lines', function () {
    [$company, $warehouse, $product] = makePartialAuditFixture(10);

    $po = PurchaseOrder::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'status'         => 'pending',
    ]);
    $item = PurchaseOrderItem::create([
        'company_uuid'        => $company,
        'purchase_order_uuid' => $po->uuid,
        'product_uuid'        => $product->uuid,
        'warehouse_uuid'      => $warehouse->uuid,
        'quantity'            => 10,
        'quantity_received'   => 0,
        'unit_price'          => 5,
        'status'              => 'pending',
    ]);

    (new PurchaseOrderController())->receive(
        Request::create('/', 'POST', ['items' => [['uuid' => $item->uuid, 'quantity_received' => 4]]]),
        $po->uuid
    );

    expect($po->fresh()->status)->toBe('partial');

    $audit = Audit::where('auditable_uuid', $po->uuid)->first();

    expect($audit)->not->toBeNull()
        ->and($audit->event_type)->toBe('po_received')
        ->and($audit->type)->toBe('partially_received')
        ->and($audit->meta['received_items'][0]['quantity_received'])->toBe(4);
});

test('a partial fulfillment logs an audit event carrying the fulfilled lines', function () {
    [$company, $warehouse, $product] = makePartialAuditFixture(10);

    Inventory::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'product_uuid'   => $product->uuid,
        'quantity'       => 50,
        'status'         => 'active',
    ]);

    $so = SalesOrder::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'status'         => 'pending',
    ]);
    $item = SalesOrderItem::create([
        'company_uuid'     => $company,
        'sales_order_uuid' => $so->uuid,
        'product_uuid'     => $product->uuid,
        'warehouse_uuid'   => $warehouse->uuid,
        'quantity'         => 10,
        'unit_price'       => 9,
        'status'           => 'pending',
    ]);

    (new SalesOrderController())->fulfill(
        Request::create('/', 'POST', ['items' => [['uuid' => $item->uuid, 'quantity_fulfilled' => 3]]]),
        $so->uuid
    );

    expect($so->fresh()->status)->toBe('partial');

    $audit = Audit::where('auditable_uuid', $so->uuid)->first();

    expect($audit)->not->toBeNull()
        ->and($audit->event_type)->toBe('so_fulfilled')
        ->and($audit->type)->toBe('partially_fulfilled')
        ->and($audit->meta['fulfilled_items'][0]['quantity_fulfilled'])->toBe(3);
});

test('a full receipt still logs the completed event type', function () {
    [$company, $warehouse, $product] = makePartialAuditFixture(10);

    $po = PurchaseOrder::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'status'         => 'pending',
    ]);
    $item = PurchaseOrderItem::create([
        'company_uuid'        => $company,
        'purchase_order_uuid' => $po->uuid,
        'product_uuid'        => $product->uuid,
        'warehouse_uuid'      => $warehouse->uuid,
        'quantity'            => 10,
        'quantity_received'   => 0,
        'unit_price'          => 5,
        'status'              => 'pending',
    ]);

    (new PurchaseOrderController())->receive(
        Request::create('/', 'POST', ['items' => [['uuid' => $item->uuid, 'quantity_received' => 10]]]),
        $po->uuid
    );

    $audit = Audit::where('auditable_uuid', $po->uuid)->first();

    expect($po->fresh()->status)->toBe('received')
        ->and($audit)->not->toBeNull()
        ->and($audit->type)->toBe('received');
});
