<?php

use Fleetbase\Pallet\Http\Resources\Internal\v1\Inventory as InventoryResource;
use Fleetbase\Pallet\Http\Resources\Internal\v1\StockTransaction as StockTransactionResource;
use Fleetbase\Pallet\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The UI design treats a warehouse quantity as a set, not a number, and treats the
 * ledger as the answer to "why is this number what it is". Neither was expressible:
 * inventory carried only on-hand / reserved / available, and a stock transaction
 * recorded a delta with no resulting balance.
 */
function newInventoryRow(array $attributes = []): Inventory
{
    $company = (string) Str::uuid();
    session(['company' => $company]);

    return Inventory::create(array_merge([
        'company_uuid' => $company,
        'product_uuid' => (string) Str::uuid(),
        'quantity'     => 100,
        'status'       => 'active',
    ], $attributes));
}

test('the three missing quantity slots persist', function () {
    $inventory = newInventoryRow([
        'in_transit'  => 12,
        'on_order'    => 40,
        'quarantined' => 7,
    ]);

    $inventory->refresh();

    expect($inventory->in_transit)->toBe(12)
        ->and($inventory->on_order)->toBe(40)
        ->and($inventory->quarantined)->toBe(7);
});

test('the new slots default to zero rather than null', function () {
    $inventory = newInventoryRow()->refresh();

    expect($inventory->in_transit)->toBe(0)
        ->and($inventory->on_order)->toBe(0)
        ->and($inventory->quarantined)->toBe(0);
});

test('available quantity still derives from on-hand minus reserved only', function () {
    // Quarantined stock is deliberately NOT subtracted here — that would change stock
    // maths the reserve/commit chain is tested against, so it is its own decision.
    $inventory = newInventoryRow(['quantity' => 100, 'reserved_quantity' => 30, 'quarantined' => 25]);
    $inventory->refresh();

    expect($inventory->available_quantity)->toBe(70);
});

test('the inventory resource exposes the full six-slot set', function () {
    $inventory = newInventoryRow(['quantity' => 50, 'reserved_quantity' => 5, 'in_transit' => 3, 'on_order' => 9, 'quarantined' => 1]);
    $payload   = (new InventoryResource($inventory->refresh()))->toArray(Request::create('/'));

    expect($payload)->toHaveKeys(['quantity', 'reserved_quantity', 'available_quantity', 'in_transit', 'on_order', 'quarantined'])
        ->and($payload['in_transit'])->toBe(3)
        ->and($payload['on_order'])->toBe(9)
        ->and($payload['quarantined'])->toBe(1);
});

test('a recorded stock transaction carries the balance it left behind', function () {
    $inventory = newInventoryRow(['quantity' => 100]);

    $transaction = $inventory->recordStockTransaction('adjustment', -15);

    expect($transaction)->not->toBeNull()
        ->and($transaction->balance_after)->toBe(100);
});

test('balance_after tracks the on-hand quantity at the time of the movement', function () {
    $inventory = newInventoryRow(['quantity' => 100]);

    $inventory->quantity = 80;
    $inventory->save();
    $first = $inventory->recordStockTransaction('issue', -20);

    $inventory->quantity = 95;
    $inventory->save();
    $second = $inventory->recordStockTransaction('receipt', 15);

    expect($first->balance_after)->toBe(80)
        ->and($second->balance_after)->toBe(95);
});

test('the stock transaction resource exposes balance_after', function () {
    $inventory   = newInventoryRow(['quantity' => 42]);
    $transaction = $inventory->recordStockTransaction('adjustment', 7);
    $payload     = (new StockTransactionResource($transaction))->toArray(Request::create('/'));

    expect($payload)->toHaveKey('balance_after')
        ->and($payload['balance_after'])->toBe(42);
});
