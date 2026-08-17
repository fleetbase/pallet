<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Pallet\Http\Resources\IndexInventory;
use Fleetbase\Pallet\Models\Batch;
use Fleetbase\Pallet\Models\BinLocation;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Fleetbase\Pallet\Models\Supplier;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseZone;
use Fleetbase\Support\Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class InventoryController extends PalletResourceController
{
    public $resource = 'inventory';

    public function queryRecord(Request $request)
    {
        $single = $request->boolean('single');
        // sort set null as we handle via custom query
        $request->request->add(['sort' => null]);

        $data = $this->model->queryFromRequest($request, function ($query) {
            // hotfix! fix the selected columns
            $queryBuilder = $query->getQuery();
            array_shift($queryBuilder->columns);

            // use summarize scope
            $query->summarizeByProduct();
        });

        if ($single) {
            $data = Arr::first($data);

            if (!$data) {
                return response()->error(Str::title($this->resourceSingularlName) . ' not found', 404);
            }

            if (Http::isInternalRequest($request)) {
                IndexInventory::wrap($this->resourceSingularlName);

                return new IndexInventory($data);
            }

            return new IndexInventory($data);
        }

        if (Http::isInternalRequest($request)) {
            IndexInventory::wrap($this->resourcePluralName);

            return IndexInventory::collection($data);
        }

        return IndexInventory::collection($data);
    }

    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);
            $data      = $request->input('inventory');
            $productId = data_get($data, 'product_uuid');
            $product   = Product::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $productId)->orWhere('public_id', $productId))
                ->first();

            if (!$product) {
                return response()->error('Product is required to create inventory.', 422);
            }

            $variantUuid = data_get($data, 'variant_uuid');
            if ($product->has_variants && !$variantUuid) {
                return response()->error('Variant is required for inventory on products with variants.', 422);
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

            $supplierUuid = data_get($data, 'supplier_uuid');
            if ($supplierUuid) {
                $supplier = Supplier::where('company_uuid', session('company'))
                    ->where(fn ($query) => $query->where('uuid', $supplierUuid)->orWhere('public_id', $supplierUuid))
                    ->first();

                if (!$supplier) {
                    return response()->error('Selected supplier could not be found.', 422);
                }

                $supplierUuid = $supplier->uuid;
            }

            $zoneUuid = data_get($data, 'zone_uuid');
            if ($zoneUuid) {
                $zone = WarehouseZone::where('company_uuid', session('company'))
                    ->when($warehouseUuid, fn ($query) => $query->where('warehouse_uuid', $warehouseUuid))
                    ->where(fn ($query) => $query->where('uuid', $zoneUuid)->orWhere('public_id', $zoneUuid))
                    ->first();

                if (!$zone) {
                    return response()->error('Selected zone could not be found for this warehouse.', 422);
                }

                $zoneUuid = $zone->uuid;
            }

            $binLocationUuid = data_get($data, 'bin_location_uuid');
            if ($binLocationUuid) {
                $binLocation = BinLocation::where('company_uuid', session('company'))
                    ->when($warehouseUuid, fn ($query) => $query->where('warehouse_uuid', $warehouseUuid))
                    ->where(fn ($query) => $query->where('uuid', $binLocationUuid)->orWhere('public_id', $binLocationUuid))
                    ->first();

                if (!$binLocation) {
                    return response()->error('Selected bin location could not be found for this warehouse.', 422);
                }

                $binLocationUuid = $binLocation->uuid;
                $zoneUuid        = $binLocation->zone_uuid ?? $zoneUuid;
                $warehouseUuid   = $binLocation->warehouse_uuid ?? $warehouseUuid;
            }

            $quantity = (int) data_get($data, 'quantity', 0);

            // Create the batch record first
            $batch = new Batch([
                'company_uuid'        => session('company'),
                'created_by_uuid'     => session('user'),
                'product_uuid'        => $product->uuid,
                'variant_uuid'        => $variantUuid,
                'batch_number'        => data_get($data, 'batch_number', now()->format('Y-m-d-') . strtoupper(Str::random(6))),
                'quantity'            => $quantity,
                'expiry_date_at'      => data_get($data, 'expiry_date_at'),
                'manufacture_date_at' => data_get($data, 'manufacture_date_at'),
            ]);
            $batch->save();

            // Create the inventory record, explicitly setting batch_uuid
            $inventory = new Inventory([
                'company_uuid'      => session('company'),
                'created_by_uuid'   => session('user'),
                'product_uuid'      => $product->uuid,
                'variant_uuid'      => $variantUuid,
                'supplier_uuid'     => $supplierUuid,
                'warehouse_uuid'    => $warehouseUuid,
                'batch_uuid'        => $batch->uuid,
                'bin_location_uuid' => $binLocationUuid,
                'zone_uuid'         => $zoneUuid,
                'status'            => data_get($data, 'status', 'active'),
                'quantity'          => $quantity,
                'min_quantity'      => data_get($data, 'min_quantity', 0),
                'max_quantity'      => data_get($data, 'max_quantity'),
                'reorder_point'     => data_get($data, 'reorder_point'),
                'unit_cost'         => data_get($data, 'unit_cost'),
                'lot_number'        => data_get($data, 'lot_number'),
                'serial_number'     => data_get($data, 'serial_number'),
                'uom'               => data_get($data, 'uom'),
                'comments'          => data_get($data, 'comments'),
                'expiry_date_at'    => data_get($data, 'expiry_date_at'),
                'received_at'       => now(),
            ]);
            $inventory->save();

            if (Http::isInternalRequest($request)) {
                $this->resource::wrap($this->resourceSingularlName);
            }

            return new $this->resource($inventory);
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        }
    }
}
