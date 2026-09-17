<?php

use Fleetbase\Pallet\Http\Controllers\SalesOrderController;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\PickList;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\SalesOrderItem;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\Wave;
use Illuminate\Http\Request;

function makeWaveFixture(int $stock = 10, int $ordered = 4): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Wave WH', 'code' => 'WWH-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Wave Product', 'sku' => 'WVP-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'       => $company,
        'product_uuid'       => $product->uuid,
        'warehouse_uuid'     => $warehouse->uuid,
        'quantity'           => $stock,
        'reserved_quantity'  => 0,
        'available_quantity' => $stock,
        'status'             => 'active',
    ]);

    $salesOrder = SalesOrder::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'status'         => 'pending',
    ]);

    $orderItem = SalesOrderItem::create([
        'company_uuid'     => $company,
        'sales_order_uuid' => $salesOrder->uuid,
        'product_uuid'     => $product->uuid,
        'warehouse_uuid'   => $warehouse->uuid,
        'quantity'         => $ordered,
        'unit_price'       => 9,
        'status'           => 'pending',
    ]);

    $wave = Wave::create(['company_uuid' => $company, 'warehouse_uuid' => $warehouse->uuid, 'status' => 'pending']);

    $pickList = PickList::create([
        'company_uuid'     => $company,
        'warehouse_uuid'   => $warehouse->uuid,
        'sales_order_uuid' => $salesOrder->uuid,
        'wave_uuid'        => $wave->uuid,
        'status'           => 'pending',
    ]);

    return [$wave->fresh(), $pickList, $salesOrder, $orderItem, $inventory, $product];
}

test('releasing a wave allocates pick items and hard reservations', function () {
    [$wave, $pickList, , $orderItem, $inventory] = makeWaveFixture(10, 4);

    $wave->release();

    expect($wave->fresh()->status)->toBe('released');

    $items = $pickList->fresh()->items;
    expect($items)->toHaveCount(1)
        ->and((int) $items->first()->quantity_requested)->toBe(4)
        ->and($items->first()->inventory_uuid)->toBe($inventory->uuid);

    $inventory = $inventory->fresh();
    expect((int) $inventory->reserved_quantity)->toBe(4)
        ->and((int) $inventory->available_quantity)->toBe(6);

    $reservation = InventoryReservation::where('pick_list_uuid', $pickList->uuid)->first();
    expect($reservation)->not->toBeNull()
        ->and((int) $reservation->quantity)->toBe(4)
        ->and($reservation->status)->toBe('active')
        ->and($orderItem->fresh()->inventory_uuid)->toBe($inventory->uuid);
});

test('wave release allocates only available stock and leaves shortages open', function () {
    [$wave, $pickList, , , $inventory] = makeWaveFixture(3, 5);

    $wave->release();

    $item = $pickList->fresh()->items->first();
    expect((int) $item->quantity_requested)->toBe(3)
        ->and((int) $inventory->fresh()->available_quantity)->toBe(0);
});

test('picked stock is committed once by sales order fulfillment', function () {
    [$wave, $pickList, $salesOrder, $orderItem, $inventory] = makeWaveFixture(10, 4);

    $wave->release();

    $pickList = $pickList->fresh();
    $pickList->start();
    $pickItem = $pickList->items->first();
    $pickItem->markPicked(4);
    $pickList->fresh()->complete();

    (new SalesOrderController())->fulfill(
        Request::create('/', 'POST', ['items' => [['uuid' => $orderItem->uuid, 'quantity_fulfilled' => 4]]]),
        $salesOrder->uuid
    );

    $inventory = $inventory->fresh();
    expect((int) $inventory->quantity)->toBe(6)
        ->and((int) $inventory->reserved_quantity)->toBe(0)
        ->and((int) $inventory->available_quantity)->toBe(6)
        ->and($orderItem->fresh()->status)->toBe('fulfilled');
});

test('items cannot be picked unless the pick list is in progress', function () {
    [$wave, $pickList] = makeWaveFixture(10, 4);

    $wave->release();
    $pickItem = $pickList->fresh()->items->first();

    expect(fn () => $pickItem->markPicked(4))->toThrow(RuntimeException::class);

    $pickList->fresh()->start();
    expect(fn () => $pickItem->fresh()->markPicked(99))->toThrow(RuntimeException::class)
        ->and($pickItem->fresh()->markPicked(4))->toBeTrue();
});

test('waves cannot be released twice', function () {
    [$wave, $pickList, , , $inventory] = makeWaveFixture(10, 4);

    $wave->release();

    expect(fn () => $wave->fresh()->release())->toThrow(RuntimeException::class)
        ->and((int) $inventory->fresh()->reserved_quantity)->toBe(4)
        ->and($pickList->fresh()->items)->toHaveCount(1);
});
