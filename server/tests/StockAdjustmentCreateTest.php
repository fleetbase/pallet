<?php

use Fleetbase\Pallet\Http\Controllers\StockAdjustmentController;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\StockAdjustment;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;

/**
 * Creating a stock adjustment through the console returned 422
 * "There is no active transaction" and changed nothing.
 */
function adjustmentFixture(int $onHand = 110): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Adjust WH', 'code' => 'ADJ-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Adjust Product', 'sku' => 'ADJ-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'product_uuid'   => $product->uuid,
        'quantity'       => $onHand,
        'status'         => 'active',
    ]);

    return [$company, $product, $warehouse, $inventory];
}

test('an add adjustment raises on-hand stock and records before and after', function () {
    [, $product, $warehouse, $inventory] = adjustmentFixture(110);

    $response = (new StockAdjustmentController())->createRecord(Request::create('/', 'POST', [
        'stock_adjustment' => [
            'product_uuid'   => $product->uuid,
            'warehouse_uuid' => $warehouse->uuid,
            'type'           => 'add',
            'quantity'       => 5,
            'reason'         => 'Verification of adjustment flow',
        ],
    ]));

    // The controller returns a JsonResponse on failure and a Resource on success,
    // so normalise before asserting and surface the API error if there is one.
    $payload = $response instanceof Illuminate\Http\JsonResponse
        ? $response->getData(true)
        : json_decode(json_encode($response->response()->getData(true)), true);

    expect($payload)->not->toHaveKey('errors', 'adjustment failed: ' . json_encode($payload));

    $adjustment = StockAdjustment::query()->first();

    expect($adjustment)->not->toBeNull()
        ->and((int) $adjustment->before_quantity)->toBe(110)
        ->and((int) $adjustment->after_quantity)->toBe(115)
        ->and((int) $adjustment->quantity)->toBe(5)
        ->and((int) $inventory->fresh()->quantity)->toBe(115);
});

test('an adjustment cannot drive stock below zero', function () {
    [, $product, $warehouse, $inventory] = adjustmentFixture(3);

    (new StockAdjustmentController())->createRecord(Request::create('/', 'POST', [
        'stock_adjustment' => [
            'product_uuid'   => $product->uuid,
            'warehouse_uuid' => $warehouse->uuid,
            'type'           => 'remove',
            'quantity'       => 10,
            'reason'         => 'Over-removal attempt',
        ],
    ]));

    expect((int) $inventory->fresh()->quantity)->toBe(3)
        ->and(StockAdjustment::query()->count())->toBe(0);
});
