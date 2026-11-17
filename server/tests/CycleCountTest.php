<?php

use Fleetbase\Pallet\Models\CycleCount;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\StockAdjustment;
use Fleetbase\Pallet\Models\Warehouse;

function makeCycleCountFixture(array $inventoryRows): array
{
    $company   = (string) Illuminate\Support\Str::uuid();
    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Count WH', 'code' => 'CWH-' . uniqid()]);

    $inventories = [];
    foreach (array_values($inventoryRows) as $index => $row) {
        // deterministic product uuids: seedItemsFromInventory orders items by
        // product_uuid, and the atomicity test needs the guarded row last
        $product = new Product([
            'company_uuid' => $company,
            'name'         => 'Count Product ' . uniqid(),
            'sku'          => 'CP-' . uniqid(),
        ]);
        $product->uuid = sprintf('%08d-0000-4000-8000-%012d', $index, $index);
        $product->save();

        $inventories[] = Inventory::create([
            'company_uuid'       => $company,
            'product_uuid'       => $product->uuid,
            'warehouse_uuid'     => $warehouse->uuid,
            'quantity'           => $row['quantity'],
            'reserved_quantity'  => $row['reserved'] ?? 0,
            'available_quantity' => $row['quantity'] - ($row['reserved'] ?? 0),
            'status'             => 'active',
        ]);
    }

    $count = CycleCount::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'status'         => 'pending',
        'type'           => 'full',
    ]);

    return [$count->fresh(), $inventories];
}

test('approving a cycle count applies all adjustments and ledger entries', function () {
    [$count, $inventories] = makeCycleCountFixture([
        ['quantity' => 10],
        ['quantity' => 5],
    ]);

    $count->start();

    foreach ($count->fresh()->items as $item) {
        $expected = (int) $item->expected_quantity;
        $item->recordCount($expected === 10 ? 7 : 5);
    }

    $count = $count->fresh();
    $count->complete();
    $count->fresh()->approve();

    $adjusted = collect($inventories)->map(fn ($inv) => $inv->fresh()->quantity)->sort()->values()->all();
    expect($adjusted)->toBe([5, 7])
        ->and(StockAdjustment::where('company_uuid', $count->company_uuid)->count())->toBe(1);
});

test('cycle count approval is atomic when an item adjustment fails', function () {
    [$count, $inventories] = makeCycleCountFixture([
        ['quantity' => 10],
        ['quantity' => 8, 'reserved' => 6],
    ]);

    $count->start();

    foreach ($count->fresh()->items as $item) {
        // shrink both: the reserved-guarded row counts below its reserved stock
        $item->recordCount((int) $item->expected_quantity === 10 ? 4 : 2);
    }

    $count = $count->fresh();
    $count->complete();

    expect(fn () => $count->fresh()->approve())->toThrow(RuntimeException::class);

    $quantities = collect($inventories)->map(fn ($inv) => $inv->fresh()->quantity)->sort()->values()->all();
    expect($quantities)->toBe([8, 10])
        ->and($count->fresh()->status)->toBe('completed')
        ->and(StockAdjustment::where('company_uuid', $count->company_uuid)->count())->toBe(0);
});

test('counts cannot be recorded unless the cycle count is in progress', function () {
    [$count] = makeCycleCountFixture([
        ['quantity' => 10],
    ]);

    $count->start();
    $item = $count->fresh()->items->first();
    $item->recordCount(9);

    $count = $count->fresh();
    $count->complete();

    expect(fn () => $item->fresh()->recordCount(3))->toThrow(RuntimeException::class)
        ->and((int) $item->fresh()->counted_quantity)->toBe(9);
});
