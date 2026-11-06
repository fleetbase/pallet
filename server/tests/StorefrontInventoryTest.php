<?php

use Fleetbase\Pallet\Http\Controllers\StorefrontInventoryController;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;

function makeStorefrontFixture(int $stock = 20): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse       = Warehouse::create(['company_uuid' => $company, 'name' => 'SF WH', 'code' => 'SFW-' . uniqid()]);
    $storefrontUuid  = (string) Illuminate\Support\Str::uuid();
    $product         = Product::create([
        'company_uuid'            => $company,
        'name'                    => 'Storefront Product',
        'sku'                     => 'SFP-' . uniqid(),
        'storefront_product_uuid' => $storefrontUuid,
    ]);

    $inventory = Inventory::create([
        'company_uuid'       => $company,
        'product_uuid'       => $product->uuid,
        'warehouse_uuid'     => $warehouse->uuid,
        'quantity'           => $stock,
        'reserved_quantity'  => 0,
        'available_quantity' => $stock,
        'status'             => 'active',
    ]);

    return [$company, $product, $storefrontUuid, $inventory];
}

function storefrontRequest(string $storefrontUuid, array $extra = []): Request
{
    return Request::create('/', 'POST', array_merge([
        'storefront_product_uuid'  => $storefrontUuid,
        'storefront_checkout_uuid' => 'checkout-fixed',
        'storefront_line_uuid'     => 'line-fixed',
        'quantity'                 => 3,
    ], $extra));
}

test('availability reports linked product stock', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(20);

    $response = (new StorefrontInventoryController())->availability(Request::create('/', 'GET', [
        'storefront_product_uuid' => $storefrontUuid,
    ]));
    $payload = json_decode($response->getContent(), true);

    expect($payload['available'])->toBeTrue()
        ->and($payload['available_quantity'])->toBe(20)
        ->and($payload['inventory_summary'])->toBeArray();
});

test('reserving storefront stock decrements availability', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(20);

    (new StorefrontInventoryController())->reserve(storefrontRequest($storefrontUuid));

    $inventory = $inventory->fresh();
    expect((int) $inventory->reserved_quantity)->toBe(3)
        ->and((int) $inventory->available_quantity)->toBe(17)
        ->and(InventoryReservation::where('inventory_uuid', $inventory->uuid)->active()->count())->toBe(1);
});

test('replaying the same reservation key with the same quantity does not double reserve', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(20);

    $controller = new StorefrontInventoryController();
    $controller->reserve(storefrontRequest($storefrontUuid));
    $first = InventoryReservation::where('inventory_uuid', $inventory->uuid)->active()->first();

    // a retried checkout call must be idempotent
    $controller->reserve(storefrontRequest($storefrontUuid));
    $active = InventoryReservation::where('inventory_uuid', $inventory->uuid)->active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->uuid)->toBe($first->uuid)
        ->and((int) $inventory->fresh()->reserved_quantity)->toBe(3);
});

test('the same reservation key with a new quantity re-reserves without leaking stock', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(20);

    $controller = new StorefrontInventoryController();
    $controller->reserve(storefrontRequest($storefrontUuid, ['quantity' => 3]));
    $controller->reserve(storefrontRequest($storefrontUuid, ['quantity' => 5]));

    $inventory = $inventory->fresh();

    // exactly one active reservation at the new quantity, old stock returned
    expect(InventoryReservation::where('inventory_uuid', $inventory->uuid)->active()->count())->toBe(1)
        ->and((int) $inventory->reserved_quantity)->toBe(5)
        ->and((int) $inventory->available_quantity)->toBe(15);
});

test('reserving more than is available fails without holding stock', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(2);

    $response = (new StorefrontInventoryController())->reserve(storefrontRequest($storefrontUuid, ['quantity' => 10]));

    expect($response->getStatusCode())->toBe(422)
        ->and((int) $inventory->fresh()->reserved_quantity)->toBe(0)
        ->and((int) $inventory->fresh()->available_quantity)->toBe(2);
});

test('committing a reservation consumes the stock', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(20);

    $controller = new StorefrontInventoryController();
    $controller->reserve(storefrontRequest($storefrontUuid));
    $reservation = InventoryReservation::where('inventory_uuid', $inventory->uuid)->active()->first();

    $controller->commit($reservation->uuid);

    $inventory = $inventory->fresh();
    expect((int) $inventory->quantity)->toBe(17)
        ->and((int) $inventory->reserved_quantity)->toBe(0)
        ->and($reservation->fresh()->status)->toBe('fulfilled');
});

test('releasing a reservation returns the stock', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(20);

    $controller = new StorefrontInventoryController();
    $controller->reserve(storefrontRequest($storefrontUuid));
    $reservation = InventoryReservation::where('inventory_uuid', $inventory->uuid)->active()->first();

    $controller->release($reservation->uuid);

    $inventory = $inventory->fresh();
    expect((int) $inventory->quantity)->toBe(20)
        ->and((int) $inventory->reserved_quantity)->toBe(0)
        ->and((int) $inventory->available_quantity)->toBe(20)
        ->and($reservation->fresh()->status)->toBe('released');
});

test('context release frees every reservation for an abandoned checkout', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(20);

    $controller = new StorefrontInventoryController();
    $controller->reserve(storefrontRequest($storefrontUuid, ['storefront_line_uuid' => 'line-a', 'quantity' => 2]));
    $controller->reserve(storefrontRequest($storefrontUuid, ['storefront_line_uuid' => 'line-b', 'quantity' => 4]));

    expect((int) $inventory->fresh()->reserved_quantity)->toBe(6);

    $controller->releaseContext(Request::create('/', 'POST', ['storefront_checkout_uuid' => 'checkout-fixed']));

    $inventory = $inventory->fresh();
    expect((int) $inventory->reserved_quantity)->toBe(0)
        ->and((int) $inventory->available_quantity)->toBe(20)
        ->and(InventoryReservation::where('inventory_uuid', $inventory->uuid)->active()->count())->toBe(0);
});

test('context commit consumes every reservation for a captured order', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(20);

    $controller = new StorefrontInventoryController();
    $controller->reserve(storefrontRequest($storefrontUuid, ['storefront_line_uuid' => 'line-a', 'quantity' => 2]));
    $controller->reserve(storefrontRequest($storefrontUuid, ['storefront_line_uuid' => 'line-b', 'quantity' => 4]));

    $controller->commitContext(Request::create('/', 'POST', ['storefront_checkout_uuid' => 'checkout-fixed']));

    $inventory = $inventory->fresh();
    expect((int) $inventory->quantity)->toBe(14)
        ->and((int) $inventory->reserved_quantity)->toBe(0);
});

test('an expired reservation for a key is swept and re-reserved on retry', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(20);

    $controller = new StorefrontInventoryController();
    $controller->reserve(storefrontRequest($storefrontUuid, ['quantity' => 3, 'expires_at' => now()->subMinute()->toDateTimeString()]));

    expect((int) $inventory->fresh()->reserved_quantity)->toBe(3);

    $controller->reserve(storefrontRequest($storefrontUuid, ['quantity' => 3]));

    $inventory = $inventory->fresh();
    expect(InventoryReservation::where('inventory_uuid', $inventory->uuid)->active()->count())->toBe(1)
        ->and((int) $inventory->reserved_quantity)->toBe(3)
        ->and((int) $inventory->available_quantity)->toBe(17);
});

test('storefront reservations never cross company boundaries', function () {
    [, , $storefrontUuid, $inventory] = makeStorefrontFixture(20);

    (new StorefrontInventoryController())->reserve(storefrontRequest($storefrontUuid));
    $reservation = InventoryReservation::where('inventory_uuid', $inventory->uuid)->active()->first();

    // another tenant must not resolve, release, or even see it
    session(['company' => (string) Illuminate\Support\Str::uuid()]);

    $availability = json_decode((new StorefrontInventoryController())->availability(Request::create('/', 'GET', [
        'storefront_product_uuid' => $storefrontUuid,
    ]))->getContent(), true);

    expect($availability['available'])->toBeFalse()
        ->and($availability['available_quantity'])->toBe(0)
        ->and(fn () => (new StorefrontInventoryController())->release($reservation->uuid))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class)
        ->and((int) $inventory->fresh()->reserved_quantity)->toBe(3);
});
