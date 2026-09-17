<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Models\Audit;
use Fleetbase\Pallet\Models\Batch;
use Fleetbase\Pallet\Models\BinLocation;
use Fleetbase\Pallet\Models\CycleCount;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\PickList;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\StockAdjustment;
use Fleetbase\Pallet\Models\StockTransfer;
use Fleetbase\Pallet\Models\Supplier;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseZone;
use Fleetbase\Pallet\Models\Wave;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    protected const SEARCH_TYPES = [
        'products',
        'variants',
        'suppliers',
        'warehouses',
        'inventory',
        'batches',
        'purchase_orders',
        'sales_orders',
        'stock_adjustments',
        'transfers',
        'cycle_counts',
        'pick_lists',
        'waves',
        'reservations',
        'zones',
        'locations',
        'audits',
    ];

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) ($request->input('query') ?: $request->input('q')));
        $limit = max(1, min((int) $request->input('limit', 12), 24));

        if (empty($query)) {
            return response()->json(['results' => []]);
        }

        $types        = $this->requestedTypes($request);
        $perTypeLimit = max(1, (int) ceil($limit / max(count($types), 1)));
        $results      = collect();

        foreach ($types as $type) {
            $results = $results->merge($this->searchType($type, $query, $perTypeLimit));
        }

        return response()->json([
            'results' => $results->take($limit)->values(),
        ]);
    }

    protected function requestedTypes(Request $request): array
    {
        $types = $request->input('types', self::SEARCH_TYPES);

        if (is_string($types)) {
            $types = explode(',', $types);
        }

        return collect($types)
            ->map(fn ($type) => trim((string) $type))
            ->filter(fn ($type) => in_array($type, self::SEARCH_TYPES, true))
            ->values()
            ->all() ?: self::SEARCH_TYPES;
    }

    protected function searchType(string $type, string $query, int $limit): Collection
    {
        return match ($type) {
            'products'          => $this->searchProducts($query, $limit),
            'variants'          => $this->searchVariants($query, $limit),
            'suppliers'         => $this->searchSuppliers($query, $limit),
            'warehouses'        => $this->searchWarehouses($query, $limit),
            'inventory'         => $this->searchInventory($query, $limit),
            'batches'           => $this->searchBatches($query, $limit),
            'purchase_orders'   => $this->searchPurchaseOrders($query, $limit),
            'sales_orders'      => $this->searchSalesOrders($query, $limit),
            'stock_adjustments' => $this->searchStockAdjustments($query, $limit),
            'transfers'         => $this->searchTransfers($query, $limit),
            'cycle_counts'      => $this->searchCycleCounts($query, $limit),
            'pick_lists'        => $this->searchPickLists($query, $limit),
            'waves'             => $this->searchWaves($query, $limit),
            'reservations'      => $this->searchReservations($query, $limit),
            'zones'             => $this->searchZones($query, $limit),
            'locations'         => $this->searchLocations($query, $limit),
            'audits'            => $this->searchAudits($query, $limit),
            default             => collect(),
        };
    }

    protected function searchProducts(string $query, int $limit): Collection
    {
        return $this->baseSearch(Product::query(), ['public_id', 'uuid', 'name', 'description', 'sku', 'barcode', 'internal_id', 'status', 'slug'], $query, $limit)
            ->map(fn (Product $product) => $this->detailResult(
                $product->name ?: $product->public_id,
                $this->description([$product->sku, $product->barcode, $product->status]),
                'boxes-stacked',
                'Product',
                'console.pallet.catalog.products.index.details',
                [$product->public_id],
                'Pallet > Catalog > Products'
            ));
    }

    protected function searchVariants(string $query, int $limit): Collection
    {
        return $this->baseSearch(ProductVariant::with('product'), ['public_id', 'uuid', 'name', 'sku', 'barcode', 'storefront_variant_uuid', 'status'], $query, $limit)
            ->filter(fn (ProductVariant $variant) => $variant->product && $variant->product->public_id)
            ->map(fn (ProductVariant $variant) => $this->detailResult(
                $variant->display_name ?: $variant->public_id,
                $this->description(['Variant', $variant->sku, $variant->barcode, $variant->product?->name]),
                'boxes-stacked',
                'Variant',
                'console.pallet.catalog.products.index.details',
                [$variant->product->public_id],
                'Pallet > Catalog > Products'
            ));
    }

    protected function searchSuppliers(string $query, int $limit): Collection
    {
        return $this->baseSearch(Supplier::query(), ['public_id', 'uuid', 'name', 'email', 'phone', 'status'], $query, $limit)
            ->map(fn (Supplier $supplier) => $this->detailResult(
                $supplier->name ?: $supplier->public_id,
                $this->description([$supplier->email, $supplier->phone, $supplier->status]),
                'user-tie',
                'Supplier',
                'console.pallet.catalog.suppliers.index.details',
                [$supplier->public_id],
                'Pallet > Catalog > Suppliers'
            ));
    }

    protected function searchWarehouses(string $query, int $limit): Collection
    {
        return $this->baseSearch(Warehouse::query(), ['public_id', 'uuid', 'name', 'code', 'type', 'status', 'phone', 'email'], $query, $limit)
            ->map(fn (Warehouse $warehouse) => $this->detailResult(
                $warehouse->name ?: $warehouse->public_id,
                $this->description([$warehouse->code, $warehouse->type, $warehouse->status]),
                'warehouse',
                'Warehouse',
                'console.pallet.facilities.warehouses.index.details',
                [$warehouse->public_id],
                'Pallet > Facilities > Warehouses'
            ));
    }

    protected function searchInventory(string $query, int $limit): Collection
    {
        return $this->baseSearch(Inventory::with(['product', 'variant', 'warehouse']), ['public_id', 'uuid', 'lot_number', 'serial_number', 'comments', 'status'], $query, $limit, function (Builder $builder) use ($query) {
            $builder
                ->orWhereHas('product', fn (Builder $product) => $this->whereLike($product, ['name', 'sku', 'barcode', 'internal_id'], $query))
                ->orWhereHas('variant', fn (Builder $variant) => $this->whereLike($variant, ['name', 'sku', 'barcode'], $query))
                ->orWhereHas('warehouse', fn (Builder $warehouse) => $this->whereLike($warehouse, ['name', 'code'], $query));
        })->map(fn (Inventory $inventory) => $this->detailResult(
            $inventory->product?->name ?: $inventory->public_id,
            $this->description([$inventory->variant?->display_name, $inventory->warehouse?->name, $inventory->lot_number, $inventory->serial_number, $inventory->status]),
            'barcode',
            'Inventory',
            'console.pallet.inventory.index.details',
            [$inventory->public_id],
            'Pallet > Inventory'
        ));
    }

    protected function searchBatches(string $query, int $limit): Collection
    {
        return $this->baseSearch(Batch::with(['product', 'variant']), ['uuid', 'batch_number'], $query, $limit, function (Builder $builder) use ($query) {
            $builder->orWhereHas('product', fn (Builder $product) => $this->whereLike($product, ['name', 'sku', 'barcode'], $query));
        })->map(fn (Batch $batch) => $this->listResult(
            $batch->batch_number ?: $batch->uuid,
            $this->description([$batch->product?->name, $batch->variant?->display_name]),
            'layer-group',
            'Batch',
            'console.pallet.inventory.batches',
            'Pallet > Inventory > Batches',
            $query
        ));
    }

    protected function searchPurchaseOrders(string $query, int $limit): Collection
    {
        return $this->baseSearch(PurchaseOrder::with(['supplier', 'warehouse']), ['public_id', 'uuid', 'reference_code', 'reference_url', 'description', 'comments', 'currency', 'status'], $query, $limit)
            ->map(fn (PurchaseOrder $order) => $this->detailResult(
                $order->reference_code ?: $order->public_id,
                $this->description([$order->supplier?->name, $order->warehouse?->name, $order->status]),
                'file-invoice-dollar',
                'Purchase Order',
                'console.pallet.orders.purchase-orders.index.details',
                [$order->public_id],
                'Pallet > Orders > Purchase Orders'
            ));
    }

    protected function searchSalesOrders(string $query, int $limit): Collection
    {
        return $this->baseSearch(SalesOrder::with(['customer', 'warehouse']), ['public_id', 'uuid', 'customer_reference_code', 'reference_code', 'reference_url', 'description', 'comments', 'customer_type', 'status'], $query, $limit)
            ->map(fn (SalesOrder $order) => $this->detailResult(
                $order->reference_code ?: $order->customer_reference_code ?: $order->public_id,
                $this->description([$order->customer?->name, $order->warehouse?->name, $order->status]),
                'cash-register',
                'Sales Order',
                'console.pallet.orders.sales-orders.index.details',
                [$order->public_id],
                'Pallet > Orders > Sales Orders'
            ));
    }

    protected function searchStockAdjustments(string $query, int $limit): Collection
    {
        return $this->baseSearch(StockAdjustment::query(), ['public_id', 'uuid', 'type', 'reason'], $query, $limit)
            ->map(fn (StockAdjustment $adjustment) => $this->listResult(
                $adjustment->public_id ?: $adjustment->reason ?: $adjustment->uuid,
                $this->description([$adjustment->type, $adjustment->reason]),
                'sliders-h',
                'Stock Adjustment',
                'console.pallet.inventory.adjustments',
                'Pallet > Inventory > Stock Adjustments',
                $query
            ));
    }

    protected function searchTransfers(string $query, int $limit): Collection
    {
        return $this->baseSearch(StockTransfer::with(['fromWarehouse', 'toWarehouse']), ['public_id', 'uuid', 'transfer_number', 'status', 'type', 'notes'], $query, $limit)
            ->map(fn (StockTransfer $transfer) => $this->listResult(
                $transfer->transfer_number ?: $transfer->public_id,
                $this->description([$transfer->fromWarehouse?->name, $transfer->toWarehouse?->name, $transfer->status]),
                'exchange-alt',
                'Transfer',
                'console.pallet.operations.transfers',
                'Pallet > Operations > Transfers',
                $query
            ));
    }

    protected function searchCycleCounts(string $query, int $limit): Collection
    {
        return $this->baseSearch(CycleCount::with(['warehouse', 'zone']), ['public_id', 'uuid', 'count_number', 'status', 'type', 'notes'], $query, $limit)
            ->map(fn (CycleCount $count) => $this->listResult(
                $count->count_number ?: $count->public_id,
                $this->description([$count->warehouse?->name, $count->zone?->name, $count->status]),
                'clipboard-list',
                'Cycle Count',
                'console.pallet.operations.cycle-counts',
                'Pallet > Operations > Cycle Counts',
                $query
            ));
    }

    protected function searchPickLists(string $query, int $limit): Collection
    {
        return $this->baseSearch(PickList::with(['warehouse', 'wave']), ['public_id', 'uuid', 'pick_list_number', 'status', 'type', 'notes'], $query, $limit)
            ->map(fn (PickList $pickList) => $this->listResult(
                $pickList->pick_list_number ?: $pickList->public_id,
                $this->description([$pickList->warehouse?->name, $pickList->wave?->wave_number, $pickList->status]),
                'list',
                'Pick List',
                'console.pallet.operations.pick-lists',
                'Pallet > Operations > Pick Lists',
                $query
            ));
    }

    protected function searchWaves(string $query, int $limit): Collection
    {
        return $this->baseSearch(Wave::with('warehouse'), ['public_id', 'uuid', 'wave_number', 'status', 'type', 'notes'], $query, $limit)
            ->map(fn (Wave $wave) => $this->listResult(
                $wave->wave_number ?: $wave->public_id,
                $this->description([$wave->warehouse?->name, $wave->status, $wave->type]),
                'water',
                'Wave',
                'console.pallet.operations.waves',
                'Pallet > Operations > Waves',
                $query
            ));
    }

    protected function searchReservations(string $query, int $limit): Collection
    {
        return $this->baseSearch(InventoryReservation::with(['product', 'variant', 'warehouse']), ['public_id', 'uuid', 'order_uuid', 'status', 'type'], $query, $limit)
            ->map(fn (InventoryReservation $reservation) => $this->listResult(
                $reservation->public_id ?: $reservation->order_uuid ?: $reservation->uuid,
                $this->description([$reservation->product?->name, $reservation->variant?->display_name, $reservation->status]),
                'lock',
                'Reservation',
                'console.pallet.operations.reservations',
                'Pallet > Operations > Reservations',
                $query
            ));
    }

    protected function searchZones(string $query, int $limit): Collection
    {
        return $this->baseSearch(WarehouseZone::with('warehouse'), ['public_id', 'uuid', 'name', 'code', 'type', 'status'], $query, $limit)
            ->map(fn (WarehouseZone $zone) => $this->listResult(
                $zone->name ?: $zone->code ?: $zone->public_id,
                $this->description([$zone->warehouse?->name, $zone->code, $zone->status]),
                'border-all',
                'Zone',
                'console.pallet.facilities.zones',
                'Pallet > Facilities > Zones',
                $query
            ));
    }

    protected function searchLocations(string $query, int $limit): Collection
    {
        return $this->baseSearch(BinLocation::with(['warehouse', 'zone']), ['public_id', 'uuid', 'bin_number', 'barcode', 'type', 'status'], $query, $limit)
            ->map(fn (BinLocation $location) => $this->listResult(
                $location->bin_number ?: $location->public_id,
                $this->description([$location->warehouse?->name, $location->zone?->name, $location->barcode, $location->status]),
                'sitemap',
                'Location',
                'console.pallet.facilities.locations',
                'Pallet > Facilities > Locations',
                $query
            ));
    }

    protected function searchAudits(string $query, int $limit): Collection
    {
        return $this->baseSearch(Audit::query(), ['public_id', 'uuid', 'action', 'event_type', 'type', 'reason', 'comments', 'auditable_type', 'auditable_uuid'], $query, $limit)
            ->map(fn (Audit $audit) => $this->listResult(
                $audit->action ?: $audit->event_type ?: $audit->public_id,
                $this->description([$audit->event_type, $audit->reason, $audit->comments]),
                'magnifying-glass',
                'Audit',
                'console.pallet.analytics.audits',
                'Pallet > Analytics > Audits',
                $query
            ));
    }

    protected function baseSearch(Builder $builder, array $columns, string $query, int $limit, ?callable $extra = null): Collection
    {
        return $builder
            ->where('company_uuid', session('company'))
            ->where(function (Builder $builder) use ($columns, $query, $extra) {
                $this->whereLike($builder, $columns, $query);

                if ($extra) {
                    $extra($builder);
                }
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    protected function whereLike(Builder $builder, array $columns, string $query): void
    {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query) . '%';

        foreach ($columns as $column) {
            $builder->orWhere($column, 'like', $like);
        }
    }

    protected function detailResult(string $label, string $description, string $icon, string $type, string $route, array $models, string $breadcrumb): array
    {
        return [
            'label'       => $label,
            'description' => $description,
            'icon'        => $icon,
            'type'        => $type,
            'route'       => $route,
            'models'      => $models,
            'breadcrumb'  => $breadcrumb,
        ];
    }

    protected function listResult(string $label, string $description, string $icon, string $type, string $route, string $breadcrumb, string $query): array
    {
        return [
            'label'       => $label,
            'description' => $description,
            'icon'        => $icon,
            'type'        => $type,
            'route'       => $route,
            'queryParams' => [
                'query' => $query,
            ],
            'breadcrumb' => $breadcrumb,
        ];
    }

    protected function description(array $parts): string
    {
        return collect($parts)
            ->filter(fn ($part) => !empty($part))
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->unique()
            ->implode(' · ');
    }
}
