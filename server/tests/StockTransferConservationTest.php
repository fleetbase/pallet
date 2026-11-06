<?php

namespace Server\tests;

use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\StockTransfer;
use Fleetbase\Pallet\Models\StockTransferItem;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Support\Str;

/*
 * A completed stock transfer deducted the full quantity at the source and added
 * nothing at the destination: receive() added `quantity_received ?? quantity`, and
 * both controllers that create a transfer item write quantity_received as 0 rather
 * than null, so the coalesce never fired and 0 units arrived. Stock silently
 * disappeared on every transfer.
 */

function transferFixture(int $onHand, int $moving): array
{
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $product = Product::create(['name' => 'Transferred Product', 'company_uuid' => $company]);
    $from    = Warehouse::create(['name' => 'Source WH', 'company_uuid' => $company]);
    $to      = Warehouse::create(['name' => 'Destination WH', 'company_uuid' => $company]);

    $source = Inventory::create([
        'company_uuid'       => $company,
        'product_uuid'       => $product->uuid,
        'warehouse_uuid'     => $from->uuid,
        'quantity'           => $onHand,
        'available_quantity' => $onHand,
        'reserved_quantity'  => 0,
        'status'             => 'active',
    ]);

    $transfer = StockTransfer::create([
        'company_uuid'        => $company,
        'from_warehouse_uuid' => $from->uuid,
        'to_warehouse_uuid'   => $to->uuid,
        'status'              => 'approved',
    ]);

    StockTransferItem::create([
        'company_uuid'        => $company,
        'stock_transfer_uuid' => $transfer->uuid,
        'product_uuid'        => $product->uuid,
        'quantity'            => $moving,
        // exactly what the controllers write, and the reason the bug existed
        'quantity_received'   => 0,
    ]);

    return [$company, $product, $from, $to, $source, $transfer->fresh()];
}

test('a completed transfer moves the stock instead of destroying it', function () {
    [$company, $product, $from, $to, $source, $transfer] = transferFixture(78, 10);

    $transfer->ship();
    expect($source->fresh()->quantity)->toBe(68, 'shipping deducts at the source');

    $transfer->fresh()->receive();

    $destination = Inventory::where('product_uuid', $product->uuid)
        ->where('warehouse_uuid', $to->uuid)
        ->first();

    expect($destination)->not->toBeNull('receiving must create stock at the destination')
        ->and($destination->quantity)->toBe(10, 'the shipped units must arrive');

    // the whole point: nothing may be lost between the two warehouses
    expect($source->fresh()->quantity + $destination->quantity)->toBe(78, 'stock must be conserved across a transfer');
});

test('receiving records what was received on the line item', function () {
    [$company, $product, $from, $to, $source, $transfer] = transferFixture(50, 20);

    $transfer->ship();
    $transfer->fresh()->receive();

    $item = StockTransferItem::where('stock_transfer_uuid', $transfer->uuid)->first();

    expect((int) $item->quantity_received)->toBe(20, 'an audit trail that says 0 received is a lie');
});

test('receiving twice cannot double the destination stock', function () {
    [$company, $product, $from, $to, $source, $transfer] = transferFixture(50, 20);

    $transfer->ship();
    $transfer->fresh()->receive();

    // the status guard should stop this outright
    expect(fn () => $transfer->fresh()->receive())->toThrow(\RuntimeException::class);

    $destination = Inventory::where('product_uuid', $product->uuid)
        ->where('warehouse_uuid', $to->uuid)
        ->first();

    expect($destination->quantity)->toBe(20);
});
