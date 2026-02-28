<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\SalesOrderItem as SalesOrderItemResource;
use Fleetbase\Pallet\Models\SalesOrderItem;
use Fleetbase\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * SalesOrderItemController
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
     * @param  string  $salesOrderUuid
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(string $salesOrderUuid)
    {
        $items = SalesOrderItem::where('sales_order_uuid', $salesOrderUuid)
            ->with(['product', 'warehouse', 'inventory'])
            ->orderBy('created_at', 'asc')
            ->get();

        return SalesOrderItemResource::collection($items);
    }

    /**
     * Create a new line item on a Sales Order.
     *
     * POST /pallet/v1/sales-orders/{salesOrder}/items
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $salesOrderUuid
     * @return \Fleetbase\Pallet\Http\Resources\SalesOrderItem
     */
    public function store(Request $request, string $salesOrderUuid)
    {
        $input = $request->input('sales_order_item', $request->all());

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

        $item->load(['product', 'warehouse', 'inventory']);

        return new SalesOrderItemResource($item);
    }

    /**
     * Update an existing line item.
     *
     * PUT /pallet/v1/sales-orders/{salesOrder}/items/{item}
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $salesOrderUuid
     * @param  string  $itemUuid
     * @return \Fleetbase\Pallet\Http\Resources\SalesOrderItem
     */
    public function update(Request $request, string $salesOrderUuid, string $itemUuid)
    {
        $item = SalesOrderItem::where('uuid', $itemUuid)
            ->where('sales_order_uuid', $salesOrderUuid)
            ->firstOrFail();

        $input = $request->input('sales_order_item', $request->all());
        $item->fill($input);

        // Recalculate total_price if pricing fields changed
        if (isset($input['unit_price']) || isset($input['quantity'])) {
            $item->recalculateTotalPrice();
        }

        $item->save();
        $item->load(['product', 'warehouse', 'inventory']);

        return new SalesOrderItemResource($item);
    }

    /**
     * Delete a line item.
     *
     * DELETE /pallet/v1/sales-orders/{salesOrder}/items/{item}
     *
     * @param  string  $salesOrderUuid
     * @param  string  $itemUuid
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
