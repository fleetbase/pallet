<?php

use Fleetbase\Pallet\Http\Resources\Internal\v1\InventoryReservation as InventoryReservationResource;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\SalesOrderItem;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Support\Str;

/*
 * A reservation is stock held back from everyone else, and the only question the
 * reservations list has to answer about one is who it is being held for. The model has
 * carried `sales_order_uuid` and a `salesOrder` relation since the WMS refactor, and
 * the relation has been in the model's $with the whole time — so every query loaded
 * the order and the resource then emitted nothing but a raw uuid. The screen could not
 * show an order number, and the load was pure cost.
 */

/**
 * @return array{reservation: InventoryReservation, order: SalesOrder, company: string}
 */
function reservationForOrder(int $lineItems = 2): array
{
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Reservation WH', 'code' => 'RWH-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Held Product', 'sku' => 'HP-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'       => $company,
        'product_uuid'       => $product->uuid,
        'warehouse_uuid'     => $warehouse->uuid,
        'quantity'           => 50,
        'reserved_quantity'  => 0,
        'available_quantity' => 50,
        'status'             => 'active',
    ]);

    $order = SalesOrder::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'status'         => 'pending',
    ]);

    for ($i = 0; $i < $lineItems; $i++) {
        SalesOrderItem::create([
            'company_uuid'     => $company,
            'sales_order_uuid' => $order->uuid,
            'product_uuid'     => $product->uuid,
            'warehouse_uuid'   => $warehouse->uuid,
            'inventory_uuid'   => $inventory->uuid,
            'quantity'         => 3,
            'unit_price'       => 5,
            'status'           => 'pending',
        ]);
    }

    $reservation = InventoryReservation::create([
        'company_uuid'     => $company,
        'product_uuid'     => $product->uuid,
        'inventory_uuid'   => $inventory->uuid,
        'warehouse_uuid'   => $warehouse->uuid,
        'sales_order_uuid' => $order->uuid,
        'quantity'         => 6,
        'status'           => 'active',
        'type'             => 'hard',
    ]);

    return ['reservation' => $reservation, 'order' => $order, 'company' => $company];
}

test('the reservation resource carries the order number the list has to display', function () {
    ['reservation' => $reservation, 'order' => $order] = reservationForOrder();

    $payload = (new InventoryReservationResource($reservation->fresh()))->resolve(request());

    expect($payload)->toHaveKey('sales_order')
        ->and($payload['sales_order']['order_number'])->toBe($order->order_number)
        ->and($payload['sales_order']['order_number'])->not->toBeNull()
        ->and($payload['sales_order']['uuid'])->toBe($order->uuid)
        ->and($payload['sales_order']['public_id'])->toBe($order->public_id)
        ->and($payload['sales_order']['status'])->toBe($order->status);
});

test('a reservation held for no order emits no sales order at all', function () {
    // The cell distinguishes "never referenced an order" from "referenced one that has
    // since been deleted". Emitting an empty object here would make every storefront
    // hold look like an order that had gone missing.
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $product     = Product::create(['company_uuid' => $company, 'name' => 'Loose', 'sku' => 'LS-' . uniqid()]);
    $reservation = InventoryReservation::create([
        'company_uuid' => $company,
        'product_uuid' => $product->uuid,
        'quantity'     => 2,
        'status'       => 'active',
        'type'         => 'soft',
    ]);

    // resolve(), not toArray(): toArray leaves Laravel's MissingValue placeholder in
    // place under the key, and it is resolve() that drops it. Asserting on toArray
    // would pass for a key the client never receives and fail for one it does.
    $payload = (new InventoryReservationResource($reservation->fresh()))->resolve(request());

    expect($payload)->not->toHaveKey('sales_order')
        ->and($payload['sales_order_uuid'])->toBeNull();
});

test('loading a reservation does not drag the order line items with it', function () {
    // SalesOrder's own $with is ['customer', 'warehouse', 'items.product',
    // 'items.variant', 'items.warehouse', 'items.inventory']. Because salesOrder sits
    // in the reservation's $with, that whole tree was hydrated for every row of the
    // reservations list and then discarded by the resource.
    ['reservation' => $reservation] = reservationForOrder(lineItems: 3);

    $loaded = InventoryReservation::find($reservation->uuid);
    $order  = $loaded->getRelation('salesOrder');

    expect($loaded->relationLoaded('salesOrder'))->toBeTrue()
        ->and($order)->not->toBeNull()
        ->and($order->getRelations())->toBe([]);
});

test('the reservation list loads one order query for the whole page, not one per row', function () {
    // Guards the eager load itself: dropping salesOrder out of $with to avoid the
    // cascade would trade the wasted tree for an N+1 behind the resource's null check.
    ['company' => $company] = reservationForOrder();
    reservationForOrder();

    $queries = [];
    DB::listen(function ($query) use (&$queries) { $queries[] = $query->sql; });

    $reservations = InventoryReservation::limit(10)->get();
    foreach ($reservations as $reservation) {
        (new InventoryReservationResource($reservation))->resolve(request());
    }

    DB::flushQueryLog();

    $orderQueries = array_values(array_filter($queries, fn ($sql) => str_contains($sql, 'pallet_sales_orders')));
    $itemQueries  = array_values(array_filter($queries, fn ($sql) => str_contains($sql, 'pallet_sales_order_items')));

    expect($reservations)->not->toBeEmpty()
        ->and($orderQueries)->toHaveCount(1)
        ->and($itemQueries)->toHaveCount(0);
});
