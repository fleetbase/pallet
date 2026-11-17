<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\Internal\v1\InventoryReservation as InventoryReservationResource;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryReservationController extends PalletResourceController
{
    public $resource = 'inventory-reservation';

    public function createRecord(Request $request)
    {
        $data     = $request->input('inventory_reservation', $request->input('inventory-reservation', $request->all()));
        $quantity = max(1, (int) data_get($data, 'quantity', 1));

        $productId = data_get($data, 'product_uuid');
        if (!$productId) {
            return response()->error('A product UUID is required to create an inventory reservation.', 422);
        }

        $product = Product::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $productId)->orWhere('public_id', $productId))
            ->first();

        if (!$product) {
            return response()->error('A valid product is required to create an inventory reservation.', 422);
        }

        $variantUuid = data_get($data, 'variant_uuid');
        if ($product->has_variants && !$variantUuid) {
            return response()->error('Variant is required to reserve products with variants.', 422);
        }

        if ($variantUuid) {
            $variant = ProductVariant::where('company_uuid', session('company'))
                ->where('product_uuid', $product->uuid)
                ->where(fn ($query) => $query->where('uuid', $variantUuid)->orWhere('public_id', $variantUuid))
                ->first();

            if (!$variant) {
                return response()->error('Selected variant does not belong to this product.', 422);
            }

            $variantUuid = $variant->uuid;
        }

        $warehouseUuid = data_get($data, 'warehouse_uuid');
        if ($warehouseUuid) {
            $warehouse = Warehouse::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $warehouseUuid)->orWhere('public_id', $warehouseUuid))
                ->first();

            if (!$warehouse) {
                return response()->error('Selected warehouse could not be found.', 422);
            }

            $warehouseUuid = $warehouse->uuid;
        }

        try {
            return DB::transaction(function () use ($data, $quantity, $product, $variantUuid, $warehouseUuid) {
                $inventory = Inventory::where('company_uuid', session('company'))
                    ->where('product_uuid', $product->uuid)
                    ->when($variantUuid, fn ($query) => $query->where('variant_uuid', $variantUuid))
                    ->when(!$variantUuid, fn ($query) => $query->whereNull('variant_uuid'))
                    ->when($warehouseUuid, fn ($query) => $query->where('warehouse_uuid', $warehouseUuid))
                    ->whereIn('status', ['active', 'available'])
                    ->where('available_quantity', '>=', $quantity)
                    ->orderByRaw('CASE WHEN expiry_date_at IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('expiry_date_at')
                    ->lockForUpdate()
                    ->first();

                // throw, never return, on failure — returning from inside the
                // transaction closure would commit any partial mutation
                if (!$inventory || !$inventory->reserve($quantity)) {
                    throw new \RuntimeException('Insufficient inventory available for this reservation.');
                }

                $reservation = InventoryReservation::create([
                    'company_uuid'      => session('company'),
                    'product_uuid'      => $inventory->product_uuid,
                    'variant_uuid'      => $inventory->variant_uuid,
                    'inventory_uuid'    => $inventory->uuid,
                    'warehouse_uuid'    => $inventory->warehouse_uuid,
                    'sales_order_uuid'  => data_get($data, 'sales_order_uuid'),
                    'pick_list_uuid'    => data_get($data, 'pick_list_uuid'),
                    'quantity'          => $quantity,
                    'expires_at'        => data_get($data, 'expires_at'),
                    'status'            => 'active',
                    'type'              => data_get($data, 'type', 'hard'),
                    'meta'              => data_get($data, 'meta'),
                ]);

                return new InventoryReservationResource($reservation->fresh(['product', 'variant', 'inventory', 'warehouse']));
            });
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }
    }

    public function release(string $id)
    {
        $reservation = $this->findReservation($id);

        if (!$reservation->release()) {
            return response()->error('Reservation cannot be released from its current state.', 422);
        }

        return new InventoryReservationResource($reservation->fresh(['product', 'variant', 'inventory', 'warehouse']));
    }

    public function fulfill(string $id)
    {
        $reservation = $this->findReservation($id);

        if (!$reservation->fulfill()) {
            return response()->error('Reservation cannot be fulfilled from its current state or inventory is insufficient.', 422);
        }

        return new InventoryReservationResource($reservation->fresh(['product', 'variant', 'inventory', 'warehouse']));
    }

    protected function findReservation(string $id): InventoryReservation
    {
        return InventoryReservation::where('company_uuid', session('company'))
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();
    }
}
