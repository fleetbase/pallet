<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\PurchaseOrderItem as PurchaseOrderItemResource;
use Fleetbase\Pallet\Models\PurchaseOrderItem;
use Fleetbase\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * PurchaseOrderItemController
 *
 * Manages CRUD operations for individual line items on a Purchase Order.
 * Items can be created, updated, and deleted independently of the parent order,
 * allowing the frontend to manage the items list dynamically.
 *
 * Note: The actual inventory creation that happens when items are *received*
 * is handled by PurchaseOrderController::receive(), not here.
 */
class PurchaseOrderItemController extends Controller
{
    /**
     * List all items for a given Purchase Order.
     *
     * GET /pallet/v1/purchase-orders/{purchaseOrder}/items
     *
     * @param  string  $purchaseOrderUuid
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(string $purchaseOrderUuid)
    {
        $items = PurchaseOrderItem::where('purchase_order_uuid', $purchaseOrderUuid)
            ->with(['product', 'warehouse'])
            ->orderBy('created_at', 'asc')
            ->get();

        return PurchaseOrderItemResource::collection($items);
    }

    /**
     * Create a new line item on a Purchase Order.
     *
     * POST /pallet/v1/purchase-orders/{purchaseOrder}/items
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $purchaseOrderUuid
     * @return \Fleetbase\Pallet\Http\Resources\PurchaseOrderItem
     */
    public function store(Request $request, string $purchaseOrderUuid)
    {
        $input = $request->input('purchase_order_item', $request->all());

        $item = PurchaseOrderItem::create(array_merge($input, [
            'uuid'                => Str::uuid(),
            'company_uuid'        => session('company'),
            'created_by_uuid'     => session('user'),
            'purchase_order_uuid' => $purchaseOrderUuid,
            'status'              => $input['status'] ?? 'pending',
        ]));

        // Auto-calculate total_price if unit_price and quantity are provided
        $item->recalculateTotalPrice();
        $item->save();

        $item->load(['product', 'warehouse']);

        return new PurchaseOrderItemResource($item);
    }

    /**
     * Update an existing line item.
     *
     * PUT /pallet/v1/purchase-orders/{purchaseOrder}/items/{item}
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $purchaseOrderUuid
     * @param  string  $itemUuid
     * @return \Fleetbase\Pallet\Http\Resources\PurchaseOrderItem
     */
    public function update(Request $request, string $purchaseOrderUuid, string $itemUuid)
    {
        $item = PurchaseOrderItem::where('uuid', $itemUuid)
            ->where('purchase_order_uuid', $purchaseOrderUuid)
            ->firstOrFail();

        $input = $request->input('purchase_order_item', $request->all());
        $item->fill($input);

        // Recalculate total_price if pricing fields changed
        if (isset($input['unit_price']) || isset($input['quantity'])) {
            $item->recalculateTotalPrice();
        }

        $item->save();
        $item->load(['product', 'warehouse']);

        return new PurchaseOrderItemResource($item);
    }

    /**
     * Delete a line item.
     *
     * DELETE /pallet/v1/purchase-orders/{purchaseOrder}/items/{item}
     *
     * @param  string  $purchaseOrderUuid
     * @param  string  $itemUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $purchaseOrderUuid, string $itemUuid)
    {
        $item = PurchaseOrderItem::where('uuid', $itemUuid)
            ->where('purchase_order_uuid', $purchaseOrderUuid)
            ->firstOrFail();

        $item->delete();

        return response()->json(['status' => 'ok', 'message' => 'Line item deleted.']);
    }
}
