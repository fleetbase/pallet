<?php

use Fleetbase\Pallet\Http\Resources\IndexInventory;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;

/**
 * The inventory list screen binds a status column and stock figures, but the
 * summarized list resource exposed neither — the column rendered blank and the
 * stock readouts were undefined. These pin the fields the list screen needs.
 */
function inventoryListPayload(array $rows): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'List WH', 'code' => 'LWH-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'List Product', 'sku' => 'LST-' . uniqid()]);

    foreach ($rows as $row) {
        Inventory::create(array_merge([
            'company_uuid'   => $company,
            'warehouse_uuid' => $warehouse->uuid,
            'product_uuid'   => $product->uuid,
            'status'         => 'active',
        ], $row));
    }

    $summary = Inventory::where('pallet_inventories.company_uuid', $company)->summarizeByProduct()->first();

    return [(new IndexInventory($summary))->toArray(Request::create('/')), $warehouse];
}

test('the inventory list exposes the status its status column renders', function () {
    [$row] = inventoryListPayload([
        ['quantity' => 10, 'reserved_quantity' => 0, 'available_quantity' => 10],
    ]);

    expect($row)->toHaveKey('status')
        ->and($row['status'])->toBe('active');
});

test('the inventory list exposes reserved and available stock alongside on-hand', function () {
    [$row] = inventoryListPayload([
        ['quantity' => 10, 'reserved_quantity' => 4, 'available_quantity' => 6],
    ]);

    expect((int) $row['quantity'])->toBe(10)
        ->and((int) $row['reserved_quantity'])->toBe(4)
        ->and((int) $row['available_quantity'])->toBe(6);
});

test('the inventory list identifies the warehouse each summarized row belongs to', function () {
    [$row, $warehouse] = inventoryListPayload([
        ['quantity' => 7, 'reserved_quantity' => 0, 'available_quantity' => 7],
    ]);

    expect($row['warehouse_uuid'])->toBe($warehouse->uuid);
});
