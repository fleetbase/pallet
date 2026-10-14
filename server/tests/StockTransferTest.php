<?php

use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\StockTransaction;
use Fleetbase\Pallet\Models\StockTransfer;
use Fleetbase\Pallet\Models\StockTransferItem;
use Fleetbase\Pallet\Models\Warehouse;

function makeTransferFixture(int $sourceQuantity = 10, int $transferQuantity = 4): array
{
    $company = (string) Illuminate\Support\Str::uuid();

    $product = Product::create([
        'company_uuid' => $company,
        'name'         => 'Transfer Test Product',
        'sku'          => 'TT-' . uniqid(),
    ]);

    $from = Warehouse::create(['company_uuid' => $company, 'name' => 'Source WH', 'code' => 'SRC-' . uniqid()]);
    $to   = Warehouse::create(['company_uuid' => $company, 'name' => 'Dest WH', 'code' => 'DST-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'       => $company,
        'product_uuid'       => $product->uuid,
        'warehouse_uuid'     => $from->uuid,
        'quantity'           => $sourceQuantity,
        'reserved_quantity'  => 0,
        'available_quantity' => $sourceQuantity,
        'status'             => 'active',
    ]);

    $transfer = StockTransfer::create([
        'company_uuid'        => $company,
        'from_warehouse_uuid' => $from->uuid,
        'to_warehouse_uuid'   => $to->uuid,
        'status'              => 'pending',
    ]);

    StockTransferItem::create([
        'company_uuid'        => $company,
        'stock_transfer_uuid' => $transfer->uuid,
        'product_uuid'        => $product->uuid,
        'quantity'            => $transferQuantity,
    ]);

    return [$transfer->fresh(), $inventory, $product, $from, $to];
}

test('cancelling an in-transit transfer restores source stock', function () {
    [$transfer, $inventory] = makeTransferFixture(10, 4);

    $transfer->approve();
    $transfer->ship();

    expect($inventory->fresh()->quantity)->toBe(6);

    $transfer->fresh()->cancel();

    $inventory = $inventory->fresh();
    expect($inventory->quantity)->toBe(10)
        ->and($inventory->available_quantity)->toBe(10);

    $restoreLedger = StockTransaction::where('transaction_type', 'transfer_cancelled')
        ->where('source_uuid', $inventory->uuid)
        ->first();
    expect($restoreLedger)->not->toBeNull()
        ->and((int) $restoreLedger->quantity)->toBe(4);
});

test('cancelling a pending transfer does not touch stock', function () {
    [$transfer, $inventory] = makeTransferFixture(10, 4);

    $transfer->cancel();

    expect($transfer->fresh()->status)->toBe('cancelled')
        ->and($inventory->fresh()->quantity)->toBe(10);
});

test('completed transfers cannot be cancelled', function () {
    [$transfer, $inventory] = makeTransferFixture(10, 4);

    $transfer->approve();
    $transfer->ship();
    $transfer->fresh()->receive();

    expect(fn () => $transfer->fresh()->cancel())->toThrow(RuntimeException::class);
});

test('items cannot be added to a transfer once it leaves pending', function () {
    [$transfer, , $product] = makeTransferFixture(10, 4);

    session(['company' => $transfer->company_uuid]);
    $transfer->approve();

    $response = (new Fleetbase\Pallet\Http\Controllers\StockTransferItemController())->createRecord(
        Illuminate\Http\Request::create('/', 'POST', [
            'stock_transfer_item' => [
                'stock_transfer_uuid' => $transfer->uuid,
                'product_uuid'        => $product->uuid,
                'quantity'            => 2,
            ],
        ])
    );

    expect($response->getStatusCode())->toBe(422)
        ->and($transfer->fresh()->items)->toHaveCount(1);
});
