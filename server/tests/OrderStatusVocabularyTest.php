<?php

use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\SalesOrder;
use Illuminate\Support\Str;

/*
 * All four order forms offered statuses the server never writes — `draft`,
 * `processing`, `shipped`, `delivered`, `approved`, and on the create panels
 * `prospective` and `archived`, copied from an unrelated resource — while omitting
 * `partial`, which is what a partial receipt or fulfilment actually sets.
 *
 * Saving one of the invented values is not cosmetic. SalesOrderController::fulfill
 * refuses only `fulfilled` and `cancelled`, so an order parked on `shipped` stays
 * fulfillable, and no translation exists for it so the badge renders the raw string.
 *
 * These tests pin the vocabulary at the point the server defines it. The frontend list
 * lives in addon/utils/get-order-status-options.js and is asserted against the same
 * values here, so the two cannot drift apart silently again.
 */
function frontendOrderStatuses(string $type): array
{
    $source = file_get_contents(__DIR__ . '/../../addon/utils/get-order-status-options.js');

    preg_match("/return type === 'purchase' \? \[(.*?)\] : \[(.*?)\];/s", $source, $matches);
    expect($matches)->not->toBeEmpty('could not read the status lists out of the util');

    $list = $type === 'purchase' ? $matches[1] : $matches[2];

    return array_map(fn ($value) => trim($value, " '\n"), explode(',', $list));
}

test('the sales order lifecycle writes exactly the statuses the form offers', function () {
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $order = SalesOrder::create(['company_uuid' => $company, 'status' => 'pending']);

    $order->markAsPartiallyFulfilled();
    expect($order->status)->toBe('partial');

    $order->markAsFulfilled();
    expect($order->status)->toBe('fulfilled');

    expect(frontendOrderStatuses('sales'))
        ->toBe(['pending', 'partial', 'fulfilled', 'cancelled']);
});

test('the purchase order lifecycle writes exactly the statuses the form offers', function () {
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $order = PurchaseOrder::create(['company_uuid' => $company, 'status' => 'pending']);

    $order->markAsPartiallyReceived();
    expect($order->status)->toBe('partial');

    $order->markAsReceived();
    expect($order->status)->toBe('received');

    expect(frontendOrderStatuses('purchase'))
        ->toBe(['pending', 'partial', 'received', 'cancelled']);
});

test('every offered status has a translation, so no badge renders a raw value', function () {
    // `shipped` had no key, which is how an invented status reaches the screen as the
    // literal string. The forms render {{t (concat "status." status)}}.
    $translations = file_get_contents(__DIR__ . '/../../translations/en-us.yaml');

    preg_match('/^status:\n((?:  .*\n)+)/m', $translations, $matches);
    expect($matches)->not->toBeEmpty('no top-level `status:` block in the translations');

    preg_match_all('/^  ([a-z-]+):/m', $matches[1], $keys);
    $defined = $keys[1];

    foreach (array_unique(array_merge(frontendOrderStatuses('sales'), frontendOrderStatuses('purchase'))) as $status) {
        expect($defined)->toContain($status);
    }
});
