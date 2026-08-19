<?php

use Fleetbase\Pallet\Console\Commands\ReleaseExpiredReservations;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;

function makeSweepFixture(int $stock = 10): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Sweep WH', 'code' => 'SWP-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Sweep Product', 'sku' => 'SWP-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'       => $company,
        'product_uuid'       => $product->uuid,
        'warehouse_uuid'     => $warehouse->uuid,
        'quantity'           => $stock,
        'reserved_quantity'  => 0,
        'available_quantity' => $stock,
        'status'             => 'active',
    ]);

    return [$company, $product, $warehouse, $inventory];
}

function reserveFor(Inventory $inventory, int $quantity, ?string $expiresAt): InventoryReservation
{
    $inventory->reserve($quantity);

    return InventoryReservation::create([
        'company_uuid'   => $inventory->company_uuid,
        'product_uuid'   => $inventory->product_uuid,
        'inventory_uuid' => $inventory->uuid,
        'warehouse_uuid' => $inventory->warehouse_uuid,
        'quantity'       => $quantity,
        'status'         => 'active',
        'type'           => 'hard',
        'expires_at'     => $expiresAt,
        'meta'           => ['source' => 'storefront'],
    ]);
}

test('the sweep releases expired reservations and returns their stock', function () {
    [, , , $inventory] = makeSweepFixture(10);

    $expired = reserveFor($inventory, 3, now()->subMinutes(10)->toDateTimeString());

    expect((int) $inventory->fresh()->available_quantity)->toBe(7);

    $this->artisan(ReleaseExpiredReservations::class)->assertExitCode(0);

    $inventory = $inventory->fresh();
    expect($expired->fresh()->status)->toBe('released')
        ->and((int) $inventory->reserved_quantity)->toBe(0)
        ->and((int) $inventory->available_quantity)->toBe(10);
});

test('the sweep leaves unexpired and open-ended reservations alone', function () {
    [, , , $inventory] = makeSweepFixture(10);

    $future    = reserveFor($inventory, 2, now()->addHour()->toDateTimeString());
    $openEnded = reserveFor($inventory, 3, null);

    $this->artisan(ReleaseExpiredReservations::class)->assertExitCode(0);

    expect($future->fresh()->status)->toBe('active')
        ->and($openEnded->fresh()->status)->toBe('active')
        ->and((int) $inventory->fresh()->reserved_quantity)->toBe(5)
        ->and((int) $inventory->fresh()->available_quantity)->toBe(5);
});

test('the sweep is idempotent across runs', function () {
    [, , , $inventory] = makeSweepFixture(10);

    reserveFor($inventory, 4, now()->subDay()->toDateTimeString());

    $this->artisan(ReleaseExpiredReservations::class)->assertExitCode(0);
    $this->artisan(ReleaseExpiredReservations::class)->assertExitCode(0);

    $inventory = $inventory->fresh();
    expect((int) $inventory->quantity)->toBe(10)
        ->and((int) $inventory->reserved_quantity)->toBe(0)
        ->and((int) $inventory->available_quantity)->toBe(10);
});
