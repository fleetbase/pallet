<?php

use Fleetbase\Pallet\Http\Controllers\PurchaseOrderController;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\PurchaseOrderItem;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;

function makePurchaseOrderFixture(int $orderedQuantity = 10): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Receiving WH', 'code' => 'RWH-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'PO Product', 'sku' => 'POP-' . uniqid()]);

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
        'quantity'            => $orderedQuantity,
        'quantity_received'   => 0,
        'unit_price'          => 5,
        'status'              => 'pending',
    ]);

    return [$po->fresh(), $item, $warehouse, $product];
}

function receiveRequest(array $items): Request
{
    return Request::create('/pallet/int/v1/purchase-orders/receive', 'POST', ['items' => $items]);
}

test('partial receipt updates item, inventory, and order status', function () {
    [$po, $item, $warehouse, $product] = makePurchaseOrderFixture(10);

    (new PurchaseOrderController())->receive(receiveRequest([
        ['uuid' => $item->uuid, 'quantity_received' => 6],
    ]), $po->uuid);

    $item = $item->fresh();
    expect((int) $item->quantity_received)->toBe(6)
        ->and($item->status)->toBe('partial')
        ->and($po->fresh()->status)->toBe('partial');

    $inventory = Inventory::where('product_uuid', $product->uuid)->where('warehouse_uuid', $warehouse->uuid)->first();
    expect($inventory)->not->toBeNull()
        ->and((int) $inventory->quantity)->toBe(6);
});

test('receiving is capped at the outstanding quantity', function () {
    [$po, $item, $warehouse, $product] = makePurchaseOrderFixture(10);

    $controller = new PurchaseOrderController();
    $controller->receive(receiveRequest([['uuid' => $item->uuid, 'quantity_received' => 6]]), $po->uuid);
    $controller->receive(receiveRequest([['uuid' => $item->uuid, 'quantity_received' => 100]]), $po->uuid);

    $item = $item->fresh();
    expect((int) $item->quantity_received)->toBe(10)
        ->and($item->status)->toBe('received')
        ->and($po->fresh()->status)->toBe('received');

    $inventory = Inventory::where('product_uuid', $product->uuid)->where('warehouse_uuid', $warehouse->uuid)->first();
    expect((int) $inventory->quantity)->toBe(10);
});

test('received and cancelled purchase orders reject further receipts', function () {
    [$po, $item] = makePurchaseOrderFixture(10);

    $controller = new PurchaseOrderController();
    $controller->receive(receiveRequest([['uuid' => $item->uuid, 'quantity_received' => 10]]), $po->uuid);

    $response = $controller->receive(receiveRequest([['uuid' => $item->uuid, 'quantity_received' => 1]]), $po->uuid);

    expect($po->fresh()->status)->toBe('received')
        ->and($response->getStatusCode())->toBe(400)
        ->and((int) $item->fresh()->quantity_received)->toBe(10);
});
