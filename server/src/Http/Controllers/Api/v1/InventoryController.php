<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Resources\v1\Inventory as InventoryResource;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Consumable stock API — read only.
 *
 * Stock levels are a consequence of receipts, fulfilments, transfers and
 * adjustments, so there is no create or update here. A consumer that needs to move
 * stock does it through the operation that caused the movement, which is what keeps
 * the ledger and the audit trail honest.
 */
class InventoryController extends Controller
{
    protected array $relations = ['product', 'variant', 'warehouse', 'batch', 'binLocation', 'zone'];

    public function query(Request $request)
    {
        return InventoryResource::collection(
            Inventory::queryWithRequest($request, function ($query) {
                $query->with($this->relations);
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $inventory = Inventory::findRecordOrFail($id, $this->relations);
        } catch (ModelNotFoundException $exception) {
            return response()->json(['error' => 'Inventory resource not found.'], 404);
        }

        return new InventoryResource($inventory);
    }

    /**
     * How much of a product can be committed right now.
     *
     * Aggregated across every warehouse holding the product unless one is named, and
     * reported per warehouse alongside the total so a caller can decide where to
     * source from. `available` answers the question the caller actually asked —
     * whether the requested quantity can be met — rather than leaving them to
     * compare two numbers.
     */
    public function availability(Request $request)
    {
        $requested = max(1, (int) $request->input('quantity', 1));

        $product = $this->resolveProduct($request);

        if (!$product) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        $warehouse = null;

        if ($request->filled('warehouse')) {
            $warehouse = Warehouse::where('company_uuid', session('company'))
                ->where('public_id', $request->input('warehouse'))
                ->first();

            if (!$warehouse) {
                return response()->json(['error' => 'Warehouse not found.'], 404);
            }
        }

        $query = Inventory::where('company_uuid', session('company'))
            ->where('product_uuid', $product->uuid)
            ->with('warehouse');

        if ($warehouse) {
            $query->where('warehouse_uuid', $warehouse->uuid);
        }

        $records = $query->get();

        $available = (int) $records->sum('available_quantity');
        $reserved  = (int) $records->sum('reserved_quantity');
        $onHand    = (int) $records->sum('quantity');

        return response()->json([
            'object'             => 'inventory_availability',
            'product'            => $product->public_id,
            'sku'                => $product->sku,
            'warehouse'          => $warehouse?->public_id,
            'requested_quantity' => $requested,
            'available'          => $available >= $requested,
            'available_quantity' => $available,
            'reserved_quantity'  => $reserved,
            'quantity'           => $onHand,
            'shortage_quantity'  => max(0, $requested - $available),
            'out_of_stock'       => $available <= 0,
            'by_warehouse'       => $records->groupBy('warehouse_uuid')->map(function ($rows) {
                return [
                    'warehouse'          => data_get($rows->first(), 'warehouse.public_id'),
                    'warehouse_name'     => data_get($rows->first(), 'warehouse.name'),
                    'available_quantity' => (int) $rows->sum('available_quantity'),
                    'reserved_quantity'  => (int) $rows->sum('reserved_quantity'),
                    'quantity'           => (int) $rows->sum('quantity'),
                ];
            })->values(),
        ]);
    }

    /**
     * Products are addressed by public id, but a caller integrating against their own
     * catalogue usually has a SKU to hand, so both are accepted.
     */
    protected function resolveProduct(Request $request): ?Product
    {
        $query = Product::where('company_uuid', session('company'));

        if ($request->filled('product')) {
            return $query->where('public_id', $request->input('product'))->first();
        }

        if ($request->filled('sku')) {
            return $query->where('sku', $request->input('sku'))->first();
        }

        return null;
    }
}
