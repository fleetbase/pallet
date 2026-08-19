<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Models\CycleCount;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\PickList;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\StockTransfer;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\Wave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * MetricsController.
 *
 * Provides aggregated data endpoints for the Pallet dashboard widgets.
 * All endpoints are scoped to the authenticated company.
 */
class MetricsController extends Controller
{
    protected function lowStockQuery(string $companyUuid)
    {
        return Inventory::where('company_uuid', $companyUuid)
            ->where('min_quantity', '>', 0)
            ->whereColumn('available_quantity', '<=', 'min_quantity');
    }

    protected function openStatuses(): array
    {
        return ['pending', 'partial', 'partially_received', 'partially_fulfilled', 'active', 'assigned', 'released', 'approved', 'in_progress', 'in_transit'];
    }

    protected function metric(string $label, $value, string $format = 'number', ?string $footnote = null): array
    {
        return [
            'label'    => $label,
            'value'    => $value,
            'format'   => $format,
            'footnote' => $footnote,
        ];
    }

    /**
     * GET pallet/metrics/kpis.
     *
     * Returns the individual KPI metrics consumed by Pallet dashboard tiles.
     */
    public function kpis(Request $request)
    {
        $companyUuid = session('company');

        $totals = Inventory::where('company_uuid', $companyUuid)
            ->selectRaw('
                SUM(quantity) as total_units,
                SUM(available_quantity) as available_units,
                SUM(reserved_quantity) as reserved_units,
                SUM(quantity * COALESCE(unit_cost, 0)) as stock_value
            ')
            ->first();

        $productCount       = Product::where('company_uuid', $companyUuid)->count();
        $variantCount       = ProductVariant::where('company_uuid', $companyUuid)->count();
        $lowStockCount      = $this->lowStockQuery($companyUuid)->count();
        $expiringSoonCount  = Inventory::where('company_uuid', $companyUuid)->expiringSoon(30)->count();
        $openPurchaseOrders = PurchaseOrder::where('company_uuid', $companyUuid)->whereIn('status', ['pending', 'partial', 'partially_received'])->count();
        $openFulfillment    = InventoryReservation::where('company_uuid', $companyUuid)->active()->count()
            + PickList::where('company_uuid', $companyUuid)->whereIn('status', ['pending', 'assigned', 'in_progress'])->count()
            + Wave::where('company_uuid', $companyUuid)->whereIn('status', ['pending', 'released', 'in_progress'])->count();

        return response()->json([
            'total_skus'       => $this->metric('Total SKUs', $productCount + $variantCount, 'number', "{$productCount} products, {$variantCount} variants"),
            'available_units'  => $this->metric('Available Units', (int) ($totals->available_units ?? 0), 'number', 'Ready to promise'),
            'reserved_units'   => $this->metric('Reserved Units', (int) ($totals->reserved_units ?? 0), 'number', 'Committed to orders'),
            'stock_value'      => $this->metric('Stock Value', round((float) ($totals->stock_value ?? 0), 2), 'currency', 'On-hand inventory value'),
            'low_stock'        => $this->metric('Low Stock', $lowStockCount, 'number', 'At or below minimum'),
            'expiring_soon'    => $this->metric('Expiring Soon', $expiringSoonCount, 'number', 'Next 30 days'),
            'open_pos'         => $this->metric('Open POs', $openPurchaseOrders, 'number', 'Awaiting receipt'),
            'open_fulfillment' => $this->metric('Open Fulfillment', $openFulfillment, 'number', 'Reservations, waves, and picks'),
        ]);
    }

    /**
     * GET pallet/metrics/inventory-health.
     */
    public function inventoryHealth(Request $request)
    {
        $companyUuid = session('company');
        $base        = Inventory::where('company_uuid', $companyUuid);

        return response()->json([
            'total'         => (clone $base)->count(),
            'in_stock'      => (clone $base)->where('available_quantity', '>', 0)->where(function ($query) {
                $query->whereNull('min_quantity')->orWhereColumn('available_quantity', '>', 'min_quantity');
            })->count(),
            'low_stock'     => $this->lowStockQuery($companyUuid)->count(),
            'out_of_stock'  => (clone $base)->where('available_quantity', '<=', 0)->count(),
            'expired'       => (clone $base)->expired()->count(),
            'expiring_soon' => (clone $base)->expiringSoon(30)->count(),
        ]);
    }

    /**
     * GET pallet/metrics/warehouse-utilization.
     */
    public function warehouseUtilization(Request $request)
    {
        $companyUuid = session('company');
        $limit       = (int) $request->input('limit', 8);

        $warehouses = Warehouse::where('pallet_warehouses.company_uuid', $companyUuid)
            ->leftJoin('pallet_inventories', 'pallet_inventories.warehouse_uuid', '=', 'pallet_warehouses.uuid')
            ->selectRaw('
                pallet_warehouses.uuid,
                pallet_warehouses.name,
                COUNT(DISTINCT pallet_inventories.product_uuid) as sku_count,
                COALESCE(SUM(pallet_inventories.quantity), 0) as units,
                COALESCE(SUM(pallet_inventories.available_quantity), 0) as available_units,
                COALESCE(SUM(pallet_inventories.reserved_quantity), 0) as reserved_units,
                COALESCE(SUM(pallet_inventories.quantity * COALESCE(pallet_inventories.unit_cost, 0)), 0) as stock_value
            ')
            ->groupBy('pallet_warehouses.uuid', 'pallet_warehouses.name')
            ->orderByDesc('units')
            ->limit($limit)
            ->get()
            ->map(function ($warehouse) {
                return [
                    'uuid'            => $warehouse->uuid,
                    'name'            => $warehouse->name,
                    'sku_count'       => (int) $warehouse->sku_count,
                    'units'           => (int) $warehouse->units,
                    'available_units' => (int) $warehouse->available_units,
                    'reserved_units'  => (int) $warehouse->reserved_units,
                    'stock_value'     => round((float) $warehouse->stock_value, 2),
                ];
            });

        return response()->json(['warehouses' => $warehouses]);
    }

    /**
     * GET pallet/metrics/stock-movement.
     */
    public function stockMovement(Request $request)
    {
        $companyUuid = session('company');
        $days        = (int) $request->input('days', 14);

        $rows = DB::table('pallet_stock_transactions')
            ->where('company_uuid', $companyUuid)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw("DATE(COALESCE(transaction_created_at, created_at)) as date, COALESCE(transaction_type, 'movement') as type, SUM(ABS(quantity)) as quantity")
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        $totals = $rows
            ->groupBy('type')
            ->map(function ($items, $type) {
                return [
                    'type'     => $type,
                    'label'    => str_replace('_', ' ', $type),
                    'quantity' => (int) $items->sum('quantity'),
                ];
            })
            ->sortByDesc('quantity')
            ->values();

        $dates = collect(range($days - 1, 0))
            ->map(fn ($offset) => now()->subDays($offset)->toDateString())
            ->push(now()->toDateString())
            ->unique()
            ->values();

        $daily = $dates->map(function ($date) use ($rows) {
            $quantity = (int) $rows->where('date', $date)->sum('quantity');

            return [
                'date'     => $date,
                'quantity' => $quantity,
            ];
        });

        return response()->json([
            'series'         => $rows,
            'totals'         => $totals,
            'daily'          => $daily,
            'total_quantity' => (int) $totals->sum('quantity'),
        ]);
    }

    /**
     * GET pallet/metrics/fulfillment-workload.
     */
    public function fulfillmentWorkload(Request $request)
    {
        $companyUuid = session('company');

        return response()->json([
            'reservations' => InventoryReservation::where('company_uuid', $companyUuid)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status'),
            'waves'        => Wave::where('company_uuid', $companyUuid)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status'),
            'pick_lists'   => PickList::where('company_uuid', $companyUuid)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status'),
            'cycle_counts' => CycleCount::where('company_uuid', $companyUuid)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status'),
            'transfers'    => StockTransfer::where('company_uuid', $companyUuid)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status'),
        ]);
    }

    /**
     * GET pallet/metrics/reorder-risk.
     */
    public function reorderRisk(Request $request)
    {
        $companyUuid = session('company');
        $limit       = (int) $request->input('limit', 10);

        $availableStockSubquery = Inventory::query()
            ->selectRaw('COALESCE(SUM(available_quantity), 0)')
            ->whereColumn('product_uuid', 'pallet_products.uuid')
            ->where('company_uuid', $companyUuid);

        $reservedStockSubquery = Inventory::query()
            ->selectRaw('COALESCE(SUM(reserved_quantity), 0)')
            ->whereColumn('product_uuid', 'pallet_products.uuid')
            ->where('company_uuid', $companyUuid);

        $products = Product::where('company_uuid', $companyUuid)
            ->whereNotNull('reorder_point')
            ->where('reorder_point', '>', 0)
            ->with('supplier:uuid,name')
            ->select('pallet_products.*')
            ->selectSub($availableStockSubquery, 'dashboard_available_stock')
            ->selectSub($reservedStockSubquery, 'dashboard_reserved_stock')
            // filtered with WHERE, not HAVING: this query has no GROUP BY, and
            // only MySQL tolerates a HAVING clause in that position
            ->whereRaw(
                '(' . $availableStockSubquery->toSql() . ') <= pallet_products.reorder_point',
                $availableStockSubquery->getBindings()
            )
            ->orderBy('dashboard_available_stock')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'uuid'             => $product->uuid,
                    'public_id'        => $product->public_id,
                    'name'             => $product->name,
                    'sku'              => $product->sku,
                    'available_stock'  => (int) $product->dashboard_available_stock,
                    'reserved_stock'   => (int) $product->dashboard_reserved_stock,
                    'reorder_point'    => (int) $product->reorder_point,
                    'reorder_quantity' => (int) $product->reorder_quantity,
                    'supplier_name'    => $product->supplier->name ?? null,
                    'shortage'         => max(0, (int) $product->reorder_point - (int) $product->dashboard_available_stock),
                ];
            });

        return response()->json(['products' => $products]);
    }

    /**
     * GET pallet/metrics/inventory-summary.
     *
     * Returns top-level KPIs: total SKUs, total units, total stock value,
     * warehouse count, and low-stock count.
     */
    public function inventorySummary(Request $request)
    {
        $companyUuid = session('company');

        $totalSkus = Inventory::where('company_uuid', $companyUuid)
            ->selectRaw('COUNT(DISTINCT COALESCE(variant_uuid, product_uuid)) as aggregate_count')
            ->value('aggregate_count');

        $totals = Inventory::where('company_uuid', $companyUuid)
            ->selectRaw('SUM(quantity) as total_units, SUM(quantity * COALESCE(unit_cost, 0)) as total_value')
            ->first();

        $warehouseCount = Warehouse::where('company_uuid', $companyUuid)->count();

        $lowStockCount = $this->lowStockQuery($companyUuid)->count();

        return response()->json([
            'total_skus'      => $totalSkus,
            'total_units'     => (int) ($totals->total_units ?? 0),
            'total_value'     => round((float) ($totals->total_value ?? 0), 2),
            'warehouse_count' => $warehouseCount,
            'low_stock_count' => $lowStockCount,
        ]);
    }

    /**
     * GET pallet/metrics/low-stock.
     *
     * Returns products at or below their minimum stock level.
     */
    public function lowStock(Request $request)
    {
        $companyUuid = session('company');
        $limit       = (int) $request->input('limit', 10);

        $items = $this->lowStockQuery($companyUuid)
            ->with(['product:uuid,name,sku', 'variant:uuid,name,sku'])
            ->orderBy('quantity', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($inv) {
                return [
                    'uuid'      => $inv->uuid,
                    'name'      => $inv->variant->display_name ?? $inv->product->name ?? null,
                    'sku'       => $inv->variant->sku ?? $inv->product->sku ?? null,
                    'quantity'  => $inv->available_quantity,
                    'min_stock' => $inv->min_quantity,
                ];
            });

        return response()->json(['items' => $items]);
    }

    /**
     * GET pallet/metrics/po-status.
     *
     * Returns purchase order counts by status and the 5 most recent orders.
     */
    public function poStatus(Request $request)
    {
        $companyUuid = session('company');

        $counts = PurchaseOrder::where('company_uuid', $companyUuid)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $recent = PurchaseOrder::where('company_uuid', $companyUuid)
            ->with('supplier:uuid,name')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($po) {
                return [
                    'uuid'          => $po->uuid,
                    'public_id'     => $po->public_id,
                    'status'        => $po->status,
                    'supplier_name' => $po->supplier->name ?? null,
                ];
            });

        return response()->json([
            'pending'            => (int) ($counts['pending'] ?? 0),
            'partially_received' => (int) (($counts['partially_received'] ?? 0) + ($counts['partial'] ?? 0)),
            'received'           => (int) ($counts['received'] ?? 0),
            'cancelled'          => (int) ($counts['cancelled'] ?? 0),
            'recent'             => $recent,
        ]);
    }

    /**
     * GET pallet/metrics/so-status.
     *
     * Returns sales order counts by status and the 5 most recent orders.
     */
    public function soStatus(Request $request)
    {
        $companyUuid = session('company');

        $counts = SalesOrder::where('company_uuid', $companyUuid)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $recent = SalesOrder::where('company_uuid', $companyUuid)
            ->with('customer:uuid,name')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($so) {
                return [
                    'uuid'          => $so->uuid,
                    'public_id'     => $so->public_id,
                    'status'        => $so->status,
                    'customer_name' => $so->customer?->name ?? $so->customer_reference_code,
                ];
            });

        return response()->json([
            'pending'              => (int) ($counts['pending'] ?? 0),
            'partially_fulfilled'  => (int) (($counts['partially_fulfilled'] ?? 0) + ($counts['partial'] ?? 0)),
            'fulfilled'            => (int) ($counts['fulfilled'] ?? 0),
            'cancelled'            => (int) ($counts['cancelled'] ?? 0),
            'recent'               => $recent,
        ]);
    }

    /**
     * GET pallet/metrics/stock-value.
     *
     * Returns total stock value broken down by warehouse.
     */
    public function stockValue(Request $request)
    {
        $companyUuid = session('company');

        $warehouses = Inventory::where('pallet_inventories.company_uuid', $companyUuid)
            ->join('pallet_warehouses', 'pallet_inventories.warehouse_uuid', '=', 'pallet_warehouses.uuid')
            ->selectRaw('pallet_warehouses.name, SUM(pallet_inventories.quantity * COALESCE(pallet_inventories.unit_cost, 0)) as value')
            ->groupBy('pallet_warehouses.uuid', 'pallet_warehouses.name')
            ->orderByDesc('value')
            ->get()
            ->map(function ($row) {
                return [
                    'name'  => $row->name,
                    'value' => round((float) $row->value, 2),
                ];
            });

        $totalValue = $warehouses->sum('value');

        return response()->json([
            'warehouses'  => $warehouses,
            'total_value' => round($totalValue, 2),
        ]);
    }

    /**
     * GET pallet/metrics/expiring-stock.
     *
     * Returns inventory batches expiring within the given number of days.
     */
    public function expiringStock(Request $request)
    {
        $companyUuid = session('company');
        $days        = (int) $request->input('days', 30);
        $limit       = (int) $request->input('limit', 10);

        $items = Inventory::where('company_uuid', $companyUuid)
            ->whereNotNull('expiry_date_at')
            ->whereDate('expiry_date_at', '<=', now()->addDays($days))
            ->whereDate('expiry_date_at', '>=', now())
            ->where('quantity', '>', 0)
            ->with(['product:uuid,name', 'variant:uuid,name,sku,option_values'])
            ->orderBy('expiry_date_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($inv) {
                return [
                    'uuid'         => $inv->uuid,
                    'product_name' => $inv->variant->display_name ?? $inv->product->name ?? null,
                    'lot_number'   => $inv->lot_number,
                    'quantity'     => $inv->quantity,
                    'expiry_date'  => $inv->expiry_date_at,
                ];
            });

        return response()->json(['items' => $items]);
    }

    /**
     * GET pallet/metrics/top-products.
     *
     * Returns the most frequently moved products based on stock transactions.
     */
    public function topProducts(Request $request)
    {
        $companyUuid = session('company');
        $limit       = (int) $request->input('limit', 10);

        $products = DB::table('pallet_stock_transactions')
            ->join('pallet_products', 'pallet_stock_transactions.product_uuid', '=', 'pallet_products.uuid')
            ->leftJoin('pallet_product_variants', 'pallet_stock_transactions.variant_uuid', '=', 'pallet_product_variants.uuid')
            ->where('pallet_stock_transactions.company_uuid', $companyUuid)
            ->selectRaw('COALESCE(pallet_product_variants.name, pallet_products.name) as name, COALESCE(pallet_product_variants.sku, pallet_products.sku) as sku, COUNT(*) as movement_count')
            ->groupBy('pallet_products.uuid', 'pallet_products.name', 'pallet_products.sku', 'pallet_product_variants.uuid', 'pallet_product_variants.name', 'pallet_product_variants.sku')
            ->orderByDesc('movement_count')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'name'           => $row->name,
                    'sku'            => $row->sku,
                    'movement_count' => (int) $row->movement_count,
                ];
            });

        return response()->json(['products' => $products]);
    }
}
