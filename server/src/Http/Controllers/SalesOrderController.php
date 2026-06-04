<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Pallet\Http\Resources\SalesOrder as SalesOrderResource;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\SalesOrderItem;
use Fleetbase\Support\Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends PalletResourceController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'sales-order';

    /**
     * Create a new Sales Order.
     *
     * @return \Illuminate\Http\Response
     */
    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);
            $data = $request->input('sales_order');

            $salesOrder = new SalesOrder([
                'company_uuid'              => session('company'),
                'created_by_uuid'           => session('user'),
                'supplier_uuid'             => data_get($data, 'supplier_uuid'),
                'transaction_uuid'          => data_get($data, 'transaction_uuid'),
                'assigned_to_uuid'          => data_get($data, 'assigned_to_uuid'),
                'point_of_contact_uuid'     => data_get($data, 'point_of_contact_uuid'),
                'status'                    => data_get($data, 'status', 'pending'),
                'reference_code'            => data_get($data, 'reference_code'),
                'reference_url'             => data_get($data, 'reference_url'),
                'customer_reference_code'   => data_get($data, 'customer_reference_code'),
                'description'               => data_get($data, 'description'),
                'comments'                  => data_get($data, 'comments'),
                'currency'                  => data_get($data, 'currency'),
                'meta'                      => data_get($data, 'meta', []),
                'expected_delivery_at'      => data_get($data, 'expected_delivery_at'),
                'order_date_at'             => data_get($data, 'order_date_at', now()),
            ]);

            $salesOrder->save();

            if (Http::isInternalRequest($request)) {
                $this->resource::wrap($this->resourceSingularlName);
            }

            return new $this->resource($salesOrder);
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        }
    }

    /**
     * Fulfill a Sales Order.
     *
     * Processes the fulfillment of a SO by deducting inventory for each line item.
     * Supports full and partial fulfillment. Validates that sufficient available
     * stock exists before deducting. Releases any existing reservations.
     *
     * Request body:
     * {
     *   "items": [
     *     {
     *       "uuid": "<sales_order_item_uuid>",
     *       "quantity_fulfilled": 5,
     *       "inventory_uuid": "<uuid>",   // optional: specific inventory record to deduct from
     *       "notes": "Shipped via DHL"    // optional
     *     }
     *   ]
     * }
     *
     * @param string $id The SO public_id or UUID
     *
     * @return \Illuminate\Http\Response
     */
    public function fulfill(Request $request, string $id)
    {
        try {
            // Resolve the SO
            $salesOrder = SalesOrder::where(function ($q) use ($id) {
                $q->where('uuid', $id)->orWhere('public_id', $id);
            })->where('company_uuid', session('company'))->firstOrFail();

            // Guard: cannot fulfill an already-fulfilled or cancelled SO
            if (in_array($salesOrder->status, ['fulfilled', 'cancelled'])) {
                return response()->error(
                    "Sales order [{$salesOrder->public_id}] cannot be fulfilled because its status is '{$salesOrder->status}'.",
                    400
                );
            }

            $itemsInput = $request->input('items', []);

            if (empty($itemsInput)) {
                return response()->error('No items provided for fulfillment.', 422);
            }

            $fulfilledSummary  = [];
            $insufficientStock = [];

            // ------------------------------------------------------------------
            // Pre-flight stock check — validate before touching any inventory
            // ------------------------------------------------------------------
            foreach ($itemsInput as $itemData) {
                $itemUuid       = data_get($itemData, 'uuid');
                $qtyFulfill     = (int) data_get($itemData, 'quantity_fulfilled', 0);
                $inventoryUuid  = data_get($itemData, 'inventory_uuid');

                if ($qtyFulfill <= 0) {
                    continue;
                }

                $item = SalesOrderItem::where('uuid', $itemUuid)
                    ->where('sales_order_uuid', $salesOrder->uuid)
                    ->first();

                if (!$item) {
                    continue;
                }

                $warehouseUuid = $item->warehouse_uuid ?? $salesOrder->warehouse_uuid ?? null;

                // Find the inventory to deduct from
                if ($inventoryUuid) {
                    $inventory = Inventory::where('uuid', $inventoryUuid)
                        ->where('company_uuid', $salesOrder->company_uuid)
                        ->first();
                } else {
                    $inventory = Inventory::where('company_uuid', $salesOrder->company_uuid)
                        ->where('product_uuid', $item->product_uuid)
                        ->where('variant_uuid', $item->variant_uuid)
                        ->where('warehouse_uuid', $warehouseUuid)
                        ->where('status', 'available')
                        ->orderBy('expiry_date_at', 'asc') // FEFO: First Expired, First Out
                        ->first();
                }

                if (!$inventory || $inventory->available_quantity < $qtyFulfill) {
                    $insufficientStock[] = [
                        'item_uuid'          => $itemUuid,
                        'product_uuid'       => $item->product_uuid,
                        'variant_uuid'       => $item->variant_uuid,
                        'requested'          => $qtyFulfill,
                        'available'          => $inventory ? $inventory->available_quantity : 0,
                    ];
                }
            }

            if (!empty($insufficientStock)) {
                return response()->json([
                    'error'              => 'Insufficient stock for one or more items.',
                    'insufficient_stock' => $insufficientStock,
                ], 422);
            }

            // ------------------------------------------------------------------
            // All stock checks passed — process fulfillment in a transaction
            // ------------------------------------------------------------------
            DB::transaction(function () use ($salesOrder, $itemsInput, &$fulfilledSummary) {
                foreach ($itemsInput as $itemData) {
                    $itemUuid       = data_get($itemData, 'uuid');
                    $qtyFulfill     = (int) data_get($itemData, 'quantity_fulfilled', 0);
                    $inventoryUuid  = data_get($itemData, 'inventory_uuid');
                    $notes          = data_get($itemData, 'notes');

                    if ($qtyFulfill <= 0) {
                        continue;
                    }

                    $item = SalesOrderItem::where('uuid', $itemUuid)
                        ->where('sales_order_uuid', $salesOrder->uuid)
                        ->first();

                    if (!$item) {
                        continue;
                    }

                    // Cap at outstanding quantity
                    $outstanding   = max(0, $item->quantity - ($item->quantity_fulfilled ?? 0));
                    $qtyToFulfill  = min($qtyFulfill, $outstanding);

                    if ($qtyToFulfill <= 0) {
                        continue;
                    }

                    $warehouseUuid = $item->warehouse_uuid ?? $salesOrder->warehouse_uuid ?? null;

                    // Resolve inventory record
                    if ($inventoryUuid) {
                        $inventory = Inventory::where('uuid', $inventoryUuid)
                            ->where('company_uuid', $salesOrder->company_uuid)
                            ->first();
                    } else {
                        $inventory = Inventory::where('company_uuid', $salesOrder->company_uuid)
                            ->where('product_uuid', $item->product_uuid)
                            ->where('variant_uuid', $item->variant_uuid)
                            ->where('warehouse_uuid', $warehouseUuid)
                            ->where('status', 'available')
                            ->orderBy('expiry_date_at', 'asc') // FEFO
                            ->first();
                    }

                    if (!$inventory) {
                        continue;
                    }

                    // Release any reservation for this item, then deduct
                    if ($item->inventory_uuid) {
                        $inventory->releaseReservation($qtyToFulfill);
                    }

                    $inventory->deduct($qtyToFulfill);

                    // Update the SO line item
                    $newQtyFulfilled          = ($item->quantity_fulfilled ?? 0) + $qtyToFulfill;
                    $item->quantity_fulfilled = $newQtyFulfilled;
                    $item->fulfilled_at       = now();
                    $item->inventory_uuid     = $inventory->uuid;
                    $item->status             = ($newQtyFulfilled >= $item->quantity) ? 'fulfilled' : 'partial';

                    if ($notes) {
                        $item->notes = $notes;
                    }

                    $item->save();

                    $fulfilledSummary[] = [
                        'item_uuid'          => $item->uuid,
                        'product_uuid'       => $item->product_uuid,
                        'variant_uuid'       => $item->variant_uuid,
                        'quantity_fulfilled' => $qtyToFulfill,
                        'inventory_uuid'     => $inventory->uuid,
                        'status'             => $item->status,
                    ];
                }

                // ----------------------------------------------------------
                // Update the SO status based on all items
                // ----------------------------------------------------------
                $salesOrder->load('items');
                $allItems        = $salesOrder->items;
                $totalItems      = $allItems->count();
                $fulfilledItems  = $allItems->where('status', 'fulfilled')->count();
                $partialItems    = $allItems->where('status', 'partial')->count();

                if ($totalItems > 0 && $fulfilledItems === $totalItems) {
                    // All items fully fulfilled
                    $salesOrder->markAsFulfilled($fulfilledSummary);
                } elseif ($fulfilledItems > 0 || $partialItems > 0) {
                    // Partial fulfillment
                    $salesOrder->status = 'partial';
                    $salesOrder->save();
                }
            });

            // Return the refreshed SO with items
            $salesOrder->load('items');

            return new SalesOrderResource($salesOrder);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->error('Sales order not found.', 404);
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        }
    }
}
