<?php

use Fleetbase\Pallet\Http\Controllers\InventoryReservationController;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;

function makeReservationFixture(int $quantity = 10): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Res WH', 'code' => 'RES-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Res Product', 'sku' => 'RES-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'       => $company,
        'product_uuid'       => $product->uuid,
        'warehouse_uuid'     => $warehouse->uuid,
        'quantity'           => $quantity,
        'reserved_quantity'  => 0,
        'available_quantity' => $quantity,
        'status'             => 'active',
    ]);

    return [$company, $product, $warehouse, $inventory];
}

test('creating a reservation reserves stock', function () {
    [, $product, , $inventory] = makeReservationFixture(10);

    (new InventoryReservationController())->createRecord(Request::create('/', 'POST', [
        'inventory_reservation' => ['product_uuid' => $product->uuid, 'quantity' => 4],
    ]));

    $inventory = $inventory->fresh();
    expect((int) $inventory->reserved_quantity)->toBe(4)
        ->and((int) $inventory->available_quantity)->toBe(6)
        ->and(InventoryReservation::where('product_uuid', $product->uuid)->where('status', 'active')->count())->toBe(1);
});

test('an insufficient-stock reservation commits nothing', function () {
    [, $product, , $inventory] = makeReservationFixture(3);

    $response = (new InventoryReservationController())->createRecord(Request::create('/', 'POST', [
        'inventory_reservation' => ['product_uuid' => $product->uuid, 'quantity' => 5],
    ]));

    expect($response->getStatusCode())->toBe(422)
        ->and((int) $inventory->fresh()->reserved_quantity)->toBe(0)
        ->and(InventoryReservation::where('product_uuid', $product->uuid)->count())->toBe(0);
});

test('a stale reservation instance cannot release stock twice', function () {
    [, $product, , $inventory] = makeReservationFixture(10);

    (new InventoryReservationController())->createRecord(Request::create('/', 'POST', [
        'inventory_reservation' => ['product_uuid' => $product->uuid, 'quantity' => 4],
    ]));

    $reservation = InventoryReservation::where('product_uuid', $product->uuid)->first();

    // two independently-loaded instances, both believing the reservation is active
    $copyA = InventoryReservation::where('uuid', $reservation->uuid)->first();
    $copyB = InventoryReservation::where('uuid', $reservation->uuid)->first();

    expect($copyA->release())->toBeTrue()
        ->and((int) $inventory->fresh()->reserved_quantity)->toBe(0);

    // stale copy passes the in-memory guard; the in-transaction re-check must stop it
    expect($copyB->release())->toBeFalse()
        ->and((int) $inventory->fresh()->reserved_quantity)->toBe(0)
        ->and((int) $inventory->fresh()->available_quantity)->toBe(10);
});

test('fulfilling a reservation commits reserved stock', function () {
    [, $product, , $inventory] = makeReservationFixture(10);

    (new InventoryReservationController())->createRecord(Request::create('/', 'POST', [
        'inventory_reservation' => ['product_uuid' => $product->uuid, 'quantity' => 4],
    ]));

    $reservation = InventoryReservation::where('product_uuid', $product->uuid)->first();
    expect($reservation->fulfill())->toBeTrue();

    $inventory = $inventory->fresh();
    expect((int) $inventory->quantity)->toBe(6)
        ->and((int) $inventory->reserved_quantity)->toBe(0)
        ->and((int) $inventory->available_quantity)->toBe(6)
        ->and($reservation->fresh()->status)->toBe('fulfilled');

    // a stale instance must not commit again
    $stale         = InventoryReservation::where('uuid', $reservation->uuid)->first();
    $stale->status = 'active';
    expect($stale->fulfill())->toBeFalse()
        ->and((int) $inventory->fresh()->quantity)->toBe(6);
});
