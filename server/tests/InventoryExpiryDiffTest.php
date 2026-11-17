<?php

use Fleetbase\Pallet\Models\Inventory;
use Illuminate\Support\Carbon;

/*
 * Carbon 3 changed the default of `diffIn*` to a signed float, where Carbon 2 gave an
 * absolute integer. `$expiry->diffInDays(now())` for future stock would turn negative,
 * so `<= 30` would call all of it "expiring soon" and days_until_expiry would be below
 * zero. These fail on Carbon 3 if the calls lose `absolute` or the `(int)` cast.
 */
beforeEach(fn () => Carbon::setTestNow('2026-09-15 12:00:00.500000'));
afterEach(fn () => Carbon::setTestNow());

function inventoryExpiring(Carbon $at): Inventory
{
    return (new Inventory())->forceFill(['expiry_date_at' => $at]);
}

test('days until expiry is a positive whole number for future stock', function () {
    expect(inventoryExpiring(now()->addHours(252))->days_until_expiry)->toBe(10);
});

test('days until expiry is zero for expired stock and null without a date', function () {
    expect(inventoryExpiring(now()->subDays(3))->days_until_expiry)->toBe(0)
        ->and((new Inventory())->days_until_expiry)->toBeNull();
});

test('expiring soon covers the next thirty whole days only', function () {
    expect(inventoryExpiring(now()->addDays(10))->is_expiring_soon)->toBeTrue()
        ->and(inventoryExpiring(now()->addHours(732))->is_expiring_soon)->toBeTrue()
        ->and(inventoryExpiring(now()->addDays(32))->is_expiring_soon)->toBeFalse()
        ->and(inventoryExpiring(now()->subDays(3))->is_expiring_soon)->toBeFalse()
        ->and((new Inventory())->is_expiring_soon)->toBeFalse();
});
