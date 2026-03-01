<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\PurchaseOrderItem;
use Fleetbase\Pallet\Http\Resources\PurchaseOrder as PurchaseOrderResource;
use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends PalletResourceController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'purchase-order';

    /**
     * Create a new Purchase Order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);
            $data = $request->input('purchase_order');

            $purchaseOrder = new PurchaseOrder([
                'company_uuid'          => session('company'),
                'created_by_uuid'       => session('user'),
                'supplier_uuid'         => data_get($data, 'supplier_uuid'),
                'transaction_uuid'      => data_get($data, 'transaction_uuid'),
                'assigned_to_uuid'      => data_get($data, 'assigned_to_uuid'),
                'point_of_contact_uuid' => data_get($data, 'point_of_contact_uuid'),
                'status'                => data_get($data, 'status', 'pending'),
                'reference_code'        => data_get($data, 'reference_code'),
                'reference_url'         => data_get($data, 'reference_url'),
                'description'           => data_get($data, 'description'),
                'comments'              => data_get($data, 'comments'),
                'currency'              => data_get($data, 'currency'),
                'meta'                  => data_get($data, 'meta', []),
                'expected_delivery_at'  => data_get($data, 'expected_delivery_at'),
                'order_created_at'      => data_get($data, 'order_date_at', now()),
            ]);

            $purchaseOrder->save();

            if (Http::isInternalRequest($request)) {
                $this->resource::wrap($this->resourceSingularlName);
            }

            return new $this->resource($purchaseOrder);
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        }
    }

    /**
     * Receive a Purchase Order.
     *
     * Processes the receipt of goods against a PO. For each line item it either
     * creates a new Inventory record or increments an existing one in the target
     * warehouse. Supports full and partial receipts.
     *
     * Request body:
     * {
     *   "items": [
     *     {
     *       "uuid": "<purchase_order_item_uuid>",
     *       "quantity_received": 10,
     *       "lot_number": "LOT-001",
     *       "serial_number": "SN-001",
     *       "expiry_date": "2026-12-31",
     *       "bin_location_uuid": "<uuid>",
     *       "zone_uuid": "<uuid>",
     *       "notes": "Received in good condition"
     *     }
     *   ]
     * }
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  The PO public_id or UUID
     * @return \Illuminate\Http\Response
     */
    public function receive(Request $request, string $id)
    {
        try {
            // Resolve the PO
            $purchaseOrder = PurchaseOrder::where(function ($q) use ($id) {
                $q->where('uuid', $id)->orWhere('public_id', $id);
            })->where('company_uuid', session('company'))->firstOrFail();

            // Guard: cannot receive an already-received or cancelled PO
            if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
                return response()->error(
                    "Purchase order [{$purchaseOrder->public_id}] cannot be received because its status is '{$purchaseOrder->status}'.",
                    400
                );
            }

            $itemsInput = $request->input('items', []);

            if (empty($itemsInput)) {
                return response()->error('No items provided for receipt.', 422);
            }

            $receivedSummary = [];

            DB::transaction(function () use ($purchaseOrder, $itemsInput, &$receivedSummary) {
                foreach ($itemsInput as $itemData) {
                    $itemUuid        = data_get($itemData, 'uuid');
                    $qtyReceived     = (int) data_get($itemData, 'quantity_received', 0);
                    $lotNumber       = data_get($itemData, 'lot_number');
                    $serialNumber    = data_get($itemData, 'serial_number');
                    $expiryDate      = data_get($itemData, 'expiry_date');
                    $binLocationUuid = data_get($itemData, 'bin_location_uuid');
                    $zoneUuid        = data_get($itemData, 'zone_uuid');
                    $notes           = data_get($itemData, 'notes');

                    if ($qtyReceived <= 0) {
                        continue;
                    }

                    // Resolve the line item — must belong to this PO
                    $item = PurchaseOrderItem::where('uuid', $itemUuid)
                        ->where('purchase_order_uuid', $purchaseOrder->uuid)
                        ->first();

                    if (!$item) {
                        continue;
                    }

                    // Cap at outstanding quantity to prevent over-receiving
                    $outstanding  = $item->outstanding_quantity;
                    $qtyToReceive = min($qtyReceived, $outstanding);

                    if ($qtyToReceive <= 0) {
                        continue;
                    }

                    // ----------------------------------------------------------
                    // Create or increment the Inventory record
                    // ----------------------------------------------------------
                    $warehouseUuid = $item->warehouse_uuid ?? $purchaseOrder->warehouse_uuid ?? null;

                    $inventoryQuery = Inventory::where('company_uuid', $purchaseOrder->company_uuid)
                        ->where('product_uuid', $item->product_uuid)
                        ->where('warehouse_uuid', $warehouseUuid);

                    if ($lotNumber) {
                        $inventoryQuery->where('lot_number', $lotNumber);
                    }
                    if ($serialNumber) {
                        $inventoryQuery->where('serial_number', $serialNumber);
                    }
                    if ($binLocationUuid) {
                        $inventoryQuery->where('bin_location_uuid', $binLocationUuid);
                    }

                    $inventory = $inventoryQuery->first();

                    if ($inventory) {
                        // Increment existing inventory
                        $inventory->add($qtyToReceive);
                        if ($notes) {
                            $inventory->comments = $notes;
                            $inventory->save();
                        }
                    } else {
                        // Create a new inventory record
                        $inventory = Inventory::create([
                            'company_uuid'       => $purchaseOrder->company_uuid,
                            'created_by_uuid'    => session('user'),
                            'supplier_uuid'      => $purchaseOrder->supplier_uuid,
                            'product_uuid'       => $item->product_uuid,
                            'warehouse_uuid'     => $warehouseUuid,
                            'bin_location_uuid'  => $binLocationUuid,
                            'zone_uuid'          => $zoneUuid,
                            'quantity'           => $qtyToReceive,
                            'available_quantity' => $qtyToReceive,
                            'reserved_quantity'  => 0,
                            'unit_cost'          => $item->unit_cost ?? $item->unit_price,
                            'lot_number'         => $lotNumber,
                            'serial_number'      => $serialNumber,
                            'expiry_date_at'     => $expiryDate,
                            'received_at'        => now(),
                            'comments'           => $notes,
                            'status'             => 'available',
                        ]);
                    }

                    // ----------------------------------------------------------
                    // Update the PO line item
                    // ----------------------------------------------------------
                    $newQtyReceived          = $item->quantity_received + $qtyToReceive;
                    $item->quantity_received = $newQtyReceived;
                    $item->received_at       = now();
                    $item->status            = ($newQtyReceived >= $item->quantity) ? 'received' : 'partial';
                    $item->save();

                    $receivedSummary[] = [
                        'item_uuid'         => $item->uuid,
                        'product_uuid'      => $item->product_uuid,
                        'quantity_received' => $qtyToReceive,
                        'inventory_uuid'    => $inventory->uuid,
                        'status'            => $item->status,
                    ];
                }

                // ----------------------------------------------------------
                // Update the PO status based on all items
                // ----------------------------------------------------------
                $purchaseOrder->load('items');
                $allItems      = $purchaseOrder->items;
                $totalItems    = $allItems->count();
                $receivedItems = $allItems->where('status', 'received')->count();
                $partialItems  = $allItems->where('status', 'partial')->count();

                if ($totalItems > 0 && $receivedItems === $totalItems) {
                    // All items fully received
                    $purchaseOrder->markAsReceived($receivedSummary);
                } elseif ($receivedItems > 0 || $partialItems > 0) {
                    // Partial receipt
                    $purchaseOrder->status = 'partial';
                    $purchaseOrder->save();
                }
            });

            // Return the refreshed PO with items
            $purchaseOrder->load('items');

            return new PurchaseOrderResource($purchaseOrder);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->error('Purchase order not found.', 404);
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        }
    }
}
