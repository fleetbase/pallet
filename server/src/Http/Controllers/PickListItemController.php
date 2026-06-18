<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\PickListItem as PickListItemResource;
use Fleetbase\Pallet\Models\BinLocation;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\PickList;
use Fleetbase\Pallet\Models\PickListItem;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Illuminate\Http\Request;

class PickListItemController extends PalletResourceController
{
    public $resource = 'pick-list-item';

    public function createRecord(Request $request)
    {
        $data = $request->input('pick_list_item', $request->input('pick-list-item', $request->all()));
        $pickListUuid = data_get($data, 'pick_list_uuid');

        if (!$pickListUuid) {
            return response()->error('A pick list UUID is required to create a pick item.', 422);
        }

        $productId = data_get($data, 'product_uuid');

        if (!$productId) {
            return response()->error('A product UUID is required to create a pick item.', 422);
        }

        $product = Product::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $productId)->orWhere('public_id', $productId))
            ->first();

        if (!$product) {
            return response()->error('A valid product is required for this pick item.', 422);
        }

        $variantUuid = data_get($data, 'variant_uuid');
        if ($product->has_variants && !$variantUuid) {
            return response()->error('Variant is required for pick items on products with variants.', 422);
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

        $pickList = PickList::where('company_uuid', session('company'))
            ->where(function ($query) use ($pickListUuid) {
                $query->where('uuid', $pickListUuid)->orWhere('public_id', $pickListUuid);
            })
            ->firstOrFail();

        $quantityRequested = max(1, (int) data_get($data, 'quantity_requested', 1));
        $inventoryUuid     = data_get($data, 'inventory_uuid');
        $binLocationUuid   = data_get($data, 'bin_location_uuid');

        if ($inventoryUuid) {
            $inventory = Inventory::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $inventoryUuid)->orWhere('public_id', $inventoryUuid))
                ->where('product_uuid', $product->uuid)
                ->when($variantUuid, fn ($query) => $query->where('variant_uuid', $variantUuid))
                ->when(!$variantUuid, fn ($query) => $query->whereNull('variant_uuid'))
                ->when($pickList->warehouse_uuid, fn ($query, $warehouseUuid) => $query->where('warehouse_uuid', $warehouseUuid))
                ->first();

            if (!$inventory) {
                return response()->error('The selected inventory record could not be found for this pick item.', 404);
            }
        }

        if ($binLocationUuid) {
            $binLocation = BinLocation::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $binLocationUuid)->orWhere('public_id', $binLocationUuid))
                ->when($pickList->warehouse_uuid, fn ($query, $warehouseUuid) => $query->where('warehouse_uuid', $warehouseUuid))
                ->first();

            if (!$binLocation) {
                return response()->error('The selected bin location could not be found for this pick list warehouse.', 404);
            }
        }

        $item = PickListItem::create([
            'company_uuid'          => session('company'),
            'pick_list_uuid'        => $pickList->uuid,
            'product_uuid'          => $product->uuid,
            'variant_uuid'          => $variantUuid,
            'inventory_uuid'        => $inventoryUuid,
            'bin_location_uuid'     => $binLocationUuid,
            'sales_order_item_uuid' => data_get($data, 'sales_order_item_uuid'),
            'quantity_requested'    => $quantityRequested,
            'quantity_picked'       => 0,
            'sequence_number'       => (int) data_get($data, 'sequence_number', $pickList->items()->count() + 1),
            'status'                => 'pending',
            'lot_number'            => data_get($data, 'lot_number'),
            'serial_number'         => data_get($data, 'serial_number'),
            'notes'                 => data_get($data, 'notes'),
            'meta'                  => data_get($data, 'meta'),
        ]);

        return new PickListItemResource($item->fresh(['product', 'variant', 'inventory', 'binLocation']));
    }

    public function markPicked(Request $request, string $id)
    {
        $item = $this->findItem($id);
        $quantityPicked = (int) $request->input('quantity_picked', $item->quantity_requested);

        if ($quantityPicked <= 0) {
            return response()->error('Picked quantity must be greater than zero.', 422);
        }

        if ($quantityPicked > (int) $item->quantity_requested) {
            return response()->error('Picked quantity cannot exceed requested quantity.', 422);
        }

        $item->markPicked($quantityPicked, $request->input('picked_by_uuid', session('user')));

        return new PickListItemResource($item->fresh(['product', 'variant', 'inventory', 'binLocation']));
    }

    protected function findItem(string $id): PickListItem
    {
        return PickListItem::where('company_uuid', session('company'))
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();
    }
}
