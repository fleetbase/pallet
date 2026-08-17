<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Fleetbase\Pallet\Models\StockAdjustment;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Support\Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends PalletResourceController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'stock-adjustment';

    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);

            $data              = $request->input('stock_adjustment', []);
            $type              = data_get($data, 'type', 'correction');
            $requestedQuantity = (int) data_get($data, 'quantity', 0);

            if ($requestedQuantity <= 0) {
                return response()->error('Quantity must be greater than zero.', 422);
            }

            $productId = data_get($data, 'product_uuid');
            $product   = Product::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $productId)->orWhere('public_id', $productId))
                ->first();

            if (!$product) {
                return response()->error('Product is required for stock adjustments.', 422);
            }

            if ($product->has_variants && !data_get($data, 'variant_uuid')) {
                return response()->error('Variant is required for stock adjustments on products with variants.', 422);
            }

            if (data_get($data, 'variant_uuid')) {
                $variantId = data_get($data, 'variant_uuid');
                $variant   = ProductVariant::where('company_uuid', session('company'))
                    ->where('product_uuid', $product->uuid)
                    ->where(fn ($query) => $query->where('uuid', $variantId)->orWhere('public_id', $variantId))
                    ->first();

                if (!$variant) {
                    return response()->error('Selected variant does not belong to this product.', 422);
                }

                $data['variant_uuid'] = $variant->uuid;
            }

            if (!data_get($data, 'warehouse_uuid')) {
                return response()->error('Warehouse is required for stock adjustments.', 422);
            }

            $warehouseId = data_get($data, 'warehouse_uuid');
            $warehouse   = Warehouse::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $warehouseId)->orWhere('public_id', $warehouseId))
                ->first();

            if (!$warehouse) {
                return response()->error('Selected warehouse could not be found.', 422);
            }

            $data['product_uuid']   = $product->uuid;
            $data['warehouse_uuid'] = $warehouse->uuid;

            $adjustment = DB::transaction(function () use ($data, $type, $requestedQuantity, $product) {
                $inventory = $this->resolveInventory($data);

                if (!$inventory && $type === 'add') {
                    $inventory = Inventory::create([
                        'company_uuid'       => session('company'),
                        'created_by_uuid'    => session('user'),
                        'product_uuid'       => $product->uuid,
                        'variant_uuid'       => data_get($data, 'variant_uuid'),
                        'warehouse_uuid'     => data_get($data, 'warehouse_uuid'),
                        'quantity'           => 0,
                        'reserved_quantity'  => 0,
                        'available_quantity' => 0,
                        'status'             => 'active',
                        'comments'           => data_get($data, 'reason'),
                        'received_at'        => now(),
                    ]);
                }

                if (!$inventory) {
                    throw new \RuntimeException('No matching inventory record was found for this adjustment.');
                }

                $beforeQuantity   = (int) $inventory->quantity;
                $reservedQuantity = (int) $inventory->reserved_quantity;
                $afterQuantity    = $this->calculateAfterQuantity($type, $beforeQuantity, $requestedQuantity);

                if ($afterQuantity < 0) {
                    throw new \RuntimeException('Stock adjustment cannot reduce inventory below zero.');
                }

                if ($afterQuantity < $reservedQuantity) {
                    throw new \RuntimeException('Stock adjustment cannot reduce on-hand inventory below reserved stock.');
                }

                $inventory->quantity = $afterQuantity;
                $inventory->syncAvailableQuantity();
                $inventory->save();
                $inventory->recordStockTransaction('adjusted', $afterQuantity - $beforeQuantity, [
                    'adjustment_type' => $type,
                    'reason'          => data_get($data, 'reason'),
                ]);

                return StockAdjustment::create([
                    'company_uuid'       => session('company'),
                    'created_by_uuid'    => session('user'),
                    'product_uuid'       => $product->uuid,
                    'variant_uuid'       => data_get($data, 'variant_uuid'),
                    'inventory_uuid'     => $inventory->uuid,
                    'warehouse_uuid'     => data_get($data, 'warehouse_uuid'),
                    'assignee_uuid'      => data_get($data, 'assignee_uuid'),
                    'type'               => $type,
                    'reason'             => data_get($data, 'reason'),
                    'approval_required'  => (bool) data_get($data, 'approval_required', false),
                    'before_quantity'    => $beforeQuantity,
                    'after_quantity'     => $afterQuantity,
                    'quantity'           => $afterQuantity - $beforeQuantity,
                ]);
            });

            $adjustment->load(['product', 'variant', 'inventory', 'warehouse']);

            if (Http::isInternalRequest($request)) {
                $this->resource::wrap($this->resourceSingularlName);
            }

            return new $this->resource($adjustment);
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        }
    }

    protected function resolveInventory(array $data): ?Inventory
    {
        if (data_get($data, 'inventory_uuid')) {
            $inventoryId = data_get($data, 'inventory_uuid');

            return Inventory::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $inventoryId)->orWhere('public_id', $inventoryId))
                ->first();
        }

        return Inventory::where('company_uuid', session('company'))
            ->where('product_uuid', data_get($data, 'product_uuid'))
            ->where('warehouse_uuid', data_get($data, 'warehouse_uuid'))
            ->when(data_get($data, 'variant_uuid'), fn ($query, $variantUuid) => $query->where('variant_uuid', $variantUuid), fn ($query) => $query->whereNull('variant_uuid'))
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->first();
    }

    protected function calculateAfterQuantity(string $type, int $beforeQuantity, int $requestedQuantity): int
    {
        return match ($type) {
            'add', 'receive', 'found'            => $beforeQuantity + $requestedQuantity,
            'remove', 'damage', 'expiry', 'loss' => $beforeQuantity - $requestedQuantity,
            'correction', 'count'                => $requestedQuantity,
            default                              => $beforeQuantity + $requestedQuantity,
        };
    }
}
