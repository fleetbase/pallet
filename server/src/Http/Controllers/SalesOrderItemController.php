<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Resources\SalesOrderItem as SalesOrderItemResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * SalesOrderItemController.
 *
 * Manages CRUD operations for individual line items on a Sales Order.
 * Items can be created, updated, and deleted independently of the parent order,
 * allowing the frontend to manage the items list dynamically.
 *
 * Note: The actual inventory decrement that happens when items are *fulfilled*
 * is handled by SalesOrderController::fulfill(), not here.
 */
class SalesOrderItemController extends Controller
{
    /**
     * List all items for a given Sales Order.
     *
     * GET /pallet/v1/sales-orders/{salesOrder}/items
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(string $salesOrderUuid)
    {
        $items = SalesOrderItem::where('sales_order_uuid', $salesOrderUuid)
            ->with(['product', 'variant', 'warehouse', 'inventory'])
            ->orderBy('created_at', 'asc')
            ->get();

        return SalesOrderItemResource::collection($items);
    }

    /**
     * Create a new line item on a Sales Order.
     *
     * POST /pallet/v1/sales-orders/{salesOrder}/items
     *
     * @return SalesOrderItemResource
     */
    public function store(Request $request, string $salesOrderUuid)
    {
        $input   = $request->input('sales_order_item', $request->all());
        $product = Product::where('uuid', data_get($input, 'product_uuid'))->where('company_uuid', session('company'))->first();
        if ($product?->has_variants && !data_get($input, 'variant_uuid')) {
            return response()->error('Variant is required for line items on products with variants.', 422);
        }

        $item = SalesOrderItem::create(array_merge($input, [
            'uuid'             => Str::uuid(),
            'company_uuid'     => session('company'),
            'created_by_uuid'  => session('user'),
            'sales_order_uuid' => $salesOrderUuid,
            'status'           => $input['status'] ?? 'pending',
        ]));

        // Auto-calculate total_price if unit_price and quantity are provided
        $item->recalculateTotalPrice();
        $item->save();

        $item->load(['product', 'variant', 'warehouse', 'inventory']);

        return new SalesOrderItemResource($item);
    }

    /**
     * Update an existing line item.
     *
     * PUT /pallet/v1/sales-orders/{salesOrder}/items/{item}
     *
     * @return SalesOrderItemResource
     */
    public function update(Request $request, string $salesOrderUuid, string $itemUuid)
    {
        $item = SalesOrderItem::where('uuid', $itemUuid)
            ->where('sales_order_uuid', $salesOrderUuid)
            ->firstOrFail();

        $input   = $request->input('sales_order_item', $request->all());
        $product = Product::where('uuid', data_get($input, 'product_uuid', $item->product_uuid))->where('company_uuid', session('company'))->first();
        if ($product?->has_variants && !data_get($input, 'variant_uuid', $item->variant_uuid)) {
            return response()->error('Variant is required for line items on products with variants.', 422);
        }

        $item->fill($input);

        // Recalculate total_price if pricing fields changed
        if (isset($input['unit_price']) || isset($input['quantity'])) {
            $item->recalculateTotalPrice();
        }

        $item->save();
        $item->load(['product', 'variant', 'warehouse', 'inventory']);

        return new SalesOrderItemResource($item);
    }

    /**
     * Delete a line item.
     *
     * DELETE /pallet/v1/sales-orders/{salesOrder}/items/{item}
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $salesOrderUuid, string $itemUuid)
    {
        $item = SalesOrderItem::where('uuid', $itemUuid)
            ->where('sales_order_uuid', $salesOrderUuid)
            ->firstOrFail();

        $item->delete();

        return response()->json(['status' => 'ok', 'message' => 'Line item deleted.']);
    }
}
