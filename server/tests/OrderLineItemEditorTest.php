<?php

namespace Server\tests;

use Fleetbase\Pallet\Http\Controllers\PurchaseOrderItemController;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*
 * Until now line items could only be created through the API, because no screen in
 * the console offered an editor. Wiring one in makes these guards reachable by a
 * person for the first time, so they are worth pinning: a received order must not
 * be editable, and a line must not be shrunk below what has already arrived.
 */

function orderFixture(string $status = 'pending'): array
{
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $product = Product::create(['name' => 'Line Item Product', 'company_uuid' => $company]);
    $order   = PurchaseOrder::create(['company_uuid' => $company, 'status' => $status]);

    return [$company, $product, $order];
}

function postItem(PurchaseOrder $order, array $payload)
{
    $request = Request::create('/pallet/int/v1/purchase-orders/' . $order->uuid . '/items', 'POST', $payload);
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('company', $order->company_uuid);

    return (new PurchaseOrderItemController())->store($request, $order->uuid);
}

test('a line item can be added to a pending order', function () {
    [$company, $product, $order] = orderFixture();

    // the success path returns the resource itself; the 201 is added by the HTTP layer
    $response = postItem($order, ['product_uuid' => $product->uuid, 'quantity' => 5, 'unit_price' => 3]);

    expect($response)->toBeInstanceOf(\Fleetbase\Pallet\Http\Resources\Internal\v1\PurchaseOrderItem::class);

    $item = PurchaseOrderItem::where('purchase_order_uuid', $order->uuid)->first();

    expect($item)->not->toBeNull()
        ->and((int) $item->quantity)->toBe(5)
        ->and((float) $item->unit_price)->toBe(3.0);
});

test('a line item cannot be added to a received order', function () {
    [$company, $product, $order] = orderFixture('received');

    $response = postItem($order, ['product_uuid' => $product->uuid, 'quantity' => 5]);

    expect($response->getStatusCode())->toBe(422)
        ->and((string) $response->getContent())->toContain('received or cancelled');
    expect(PurchaseOrderItem::where('purchase_order_uuid', $order->uuid)->count())->toBe(0);
});

test('a line item quantity must be greater than zero', function () {
    [$company, $product, $order] = orderFixture();

    $response = postItem($order, ['product_uuid' => $product->uuid, 'quantity' => 0]);

    expect($response->getStatusCode())->toBe(422)
        ->and((string) $response->getContent())->toContain('greater than zero');
});

test('a line item cannot be shrunk below what has already been received', function () {
    [$company, $product, $order] = orderFixture();

    $item = PurchaseOrderItem::create([
        'company_uuid'        => $company,
        'purchase_order_uuid' => $order->uuid,
        'product_uuid'        => $product->uuid,
        'quantity'            => 10,
        'quantity_received'   => 6,
    ]);

    $request = Request::create('/pallet/int/v1/purchase-orders/' . $order->uuid . '/items/' . $item->uuid, 'PUT', [
        'product_uuid' => $product->uuid,
        'quantity'     => 4,
    ]);
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('company', $company);

    $response = (new PurchaseOrderItemController())->update($request, $order->uuid, $item->uuid);

    expect($response->getStatusCode())->toBe(422)
        ->and((string) $response->getContent())->toContain('already received');
    expect((int) $item->fresh()->quantity)->toBe(10, 'the line must be left as it was');
});

test('a line item holding received stock cannot be deleted', function () {
    [$company, $product, $order] = orderFixture();

    $item = PurchaseOrderItem::create([
        'company_uuid'        => $company,
        'purchase_order_uuid' => $order->uuid,
        'product_uuid'        => $product->uuid,
        'quantity'            => 10,
        'quantity_received'   => 6,
    ]);

    // destroy() takes no Request — only the two identifiers
    $response = (new PurchaseOrderItemController())->destroy($order->uuid, $item->uuid);

    expect($response->getStatusCode())->toBe(422);
    expect(PurchaseOrderItem::where('uuid', $item->uuid)->first())->not->toBeNull();
});
