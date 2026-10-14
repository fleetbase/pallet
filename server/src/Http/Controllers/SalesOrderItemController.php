<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Resources\Internal\v1\SalesOrderItem as SalesOrderItemResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Fleetbase\Pallet\Models\SalesOrder;
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
        $salesOrder = $this->resolveSalesOrder($salesOrderUuid);

        $items = SalesOrderItem::where('sales_order_uuid', $salesOrder->uuid)
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
        $salesOrder = $this->resolveSalesOrder($salesOrderUuid);

        if (in_array($salesOrder->status, ['fulfilled', 'cancelled'])) {
            return response()->error('Line items cannot be added to a fulfilled or cancelled sales order.', 422);
        }

        $input      = $request->input('sales_order_item', $request->all());
        $validation = $this->validateLineItemInput($input);

        if ($validation !== true) {
            return $validation;
        }

        $item = SalesOrderItem::create(array_merge($input, [
            'uuid'               => Str::uuid(),
            'company_uuid'       => session('company'),
            'created_by_uuid'    => session('user'),
            'sales_order_uuid'   => $salesOrder->uuid,
            'quantity_fulfilled' => 0,
            'status'             => $input['status'] ?? 'pending',
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
        $salesOrder = $this->resolveSalesOrder($salesOrderUuid);

        if (in_array($salesOrder->status, ['fulfilled', 'cancelled'])) {
            return response()->error('Line items cannot be updated on a fulfilled or cancelled sales order.', 422);
        }

        $item = SalesOrderItem::where(function ($query) use ($itemUuid) {
            $query->where('uuid', $itemUuid)->orWhere('public_id', $itemUuid);
        })
            ->where('sales_order_uuid', $salesOrder->uuid)
            ->firstOrFail();

        $input      = $request->input('sales_order_item', $request->all());
        $validation = $this->validateLineItemInput($input, $item);

        if ($validation !== true) {
            return $validation;
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
        $salesOrder = $this->resolveSalesOrder($salesOrderUuid);

        if (in_array($salesOrder->status, ['fulfilled', 'cancelled'])) {
            return response()->error('Line items cannot be deleted from a fulfilled or cancelled sales order.', 422);
        }

        $item = SalesOrderItem::where(function ($query) use ($itemUuid) {
            $query->where('uuid', $itemUuid)->orWhere('public_id', $itemUuid);
        })
            ->where('sales_order_uuid', $salesOrder->uuid)
            ->firstOrFail();

        if (($item->quantity_fulfilled ?? 0) > 0) {
            return response()->error('Line items with fulfilled stock cannot be deleted.', 422);
        }

        $item->delete();

        return response()->json(['status' => 'ok', 'message' => 'Line item deleted.']);
    }

    protected function resolveSalesOrder(string $id): SalesOrder
    {
        return SalesOrder::where(function ($query) use ($id) {
            $query->where('uuid', $id)->orWhere('public_id', $id);
        })->where('company_uuid', session('company'))->firstOrFail();
    }

    protected function validateLineItemInput(array &$input, ?SalesOrderItem $item = null)
    {
        $productUuid = data_get($input, 'product_uuid', $item?->product_uuid);
        $variantUuid = data_get($input, 'variant_uuid', $item?->variant_uuid);
        $quantity    = (int) data_get($input, 'quantity', $item?->quantity ?? 0);
        $unitPrice   = (float) data_get($input, 'unit_price', $item?->unit_price ?? 0);

        $product = Product::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $productUuid)->orWhere('public_id', $productUuid))
            ->first();

        if (!$product) {
            return response()->error('A valid product is required for this line item.', 422);
        }

        if ($quantity <= 0) {
            return response()->error('Line item quantity must be greater than zero.', 422);
        }

        if ($item && $quantity < (int) ($item->quantity_fulfilled ?? 0)) {
            return response()->error('Line item quantity cannot be less than the quantity already fulfilled.', 422);
        }

        if ($unitPrice < 0) {
            return response()->error('Line item unit price cannot be negative.', 422);
        }

        if ($product->has_variants && !$variantUuid) {
            return response()->error('Variant is required for line items on products with variants.', 422);
        }

        if ($variantUuid) {
            $variant = ProductVariant::where('company_uuid', session('company'))
                ->where('product_uuid', $product->uuid)
                ->where(fn ($query) => $query->where('uuid', $variantUuid)->orWhere('public_id', $variantUuid))
                ->first();

            if (!$variant) {
                return response()->error('Selected variant does not belong to this product.', 422);
            }

            $input['sku'] ??= $variant->sku;
            $variantUuid = $variant->uuid;
        }

        if (!$product->has_variants) {
            $variantUuid = null;
            $input['sku'] ??= $product->sku;
        }

        $input['product_uuid'] = $product->uuid;
        $input['variant_uuid'] = $variantUuid;
        $input['quantity']     = $quantity;
        $input['unit_price']   = $unitPrice;

        return true;
    }
}
