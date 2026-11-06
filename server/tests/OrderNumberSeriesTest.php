<?php

use Fleetbase\Pallet\Http\Resources\Internal\v1\PurchaseOrder as PurchaseOrderResource;
use Fleetbase\Pallet\Http\Resources\Internal\v1\SalesOrder as SalesOrderResource;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\SalesOrder;
use Illuminate\Http\Request;

/**
 * Purchase and sales orders had no number of their own: the resource set
 * `order_number` to `public_id`, so the detail panel printed the same string in
 * two adjacent fields and nothing distinguished an order from its record id.
 *
 * Every other numbered record in the module carries a real series generated on
 * create — transfers TR-, waves WAVE-, cycle counts CC-, pick lists PL-.
 */
function newPurchaseOrder(array $attributes = []): PurchaseOrder
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    return PurchaseOrder::create(array_merge(['company_uuid' => $company, 'status' => 'pending'], $attributes));
}

function newSalesOrder(array $attributes = []): SalesOrder
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    return SalesOrder::create(array_merge(['company_uuid' => $company, 'status' => 'pending'], $attributes));
}

test('a purchase order is given a PO- number on create', function () {
    $order = newPurchaseOrder();

    expect($order->order_number)->toStartWith('PO-')
        ->and($order->order_number)->not->toBe($order->public_id);
});

test('a sales order is given an SO- number on create', function () {
    $order = newSalesOrder();

    expect($order->order_number)->toStartWith('SO-')
        ->and($order->order_number)->not->toBe($order->public_id);
});

test('order numbers are distinct across records', function () {
    $numbers = collect(range(1, 5))->map(fn () => newPurchaseOrder()->order_number);

    expect($numbers->unique())->toHaveCount(5);
});

test('an order number supplied explicitly is kept', function () {
    $order = newPurchaseOrder(['order_number' => 'PO-MIGRATED-0001']);

    expect($order->order_number)->toBe('PO-MIGRATED-0001');
});

test('the purchase order resource emits the series rather than the record id', function () {
    $order   = newPurchaseOrder();
    $payload = (new PurchaseOrderResource($order))->toArray(Request::create('/'));

    expect($payload['order_number'])->toStartWith('PO-')
        ->and($payload['order_number'])->not->toBe($payload['public_id']);
});

test('the sales order resource emits the series rather than the record id', function () {
    $order   = newSalesOrder();
    $payload = (new SalesOrderResource($order))->toArray(Request::create('/'));

    expect($payload['order_number'])->toStartWith('SO-')
        ->and($payload['order_number'])->not->toBe($payload['public_id']);
});
