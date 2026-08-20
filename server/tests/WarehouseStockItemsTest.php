<?php

use Fleetbase\Pallet\Http\Resources\Warehouse as WarehouseResource;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;

/**
 * The warehouse list renders a STOCK ITEMS column, but the resource emitted no
 * such field, so the column showed "-" for every warehouse regardless of how
 * much stock it held.
 */
function warehouseResourcePayload(int $inventoryRows): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Counted WH', 'code' => 'CNT-' . uniqid()]);

    for ($i = 0; $i < $inventoryRows; $i++) {
        $product = Product::create(['company_uuid' => $company, 'name' => 'P' . $i, 'sku' => 'CNT-' . uniqid()]);

        Inventory::create([
            'company_uuid'   => $company,
            'warehouse_uuid' => $warehouse->uuid,
            'product_uuid'   => $product->uuid,
            'quantity'       => 10,
            'status'         => 'active',
        ]);
    }

    return (new WarehouseResource(Warehouse::find($warehouse->uuid)))->toArray(Request::create('/'));
}

test('a warehouse reports how many stock items it holds', function () {
    expect(warehouseResourcePayload(3)['stock_items'])->toBe(3);
});

test('a warehouse with no stock reports zero rather than omitting the field', function () {
    $payload = warehouseResourcePayload(0);

    expect($payload)->toHaveKey('stock_items')
        ->and($payload['stock_items'])->toBe(0);
});
