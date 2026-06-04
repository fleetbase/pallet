<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\Warehouse;
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
            ->whereColumn('quantity', '<=', 'min_quantity');
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
            ->selectRaw('SUM(quantity) as total_units, SUM(quantity * unit_cost) as total_value')
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
                    'quantity'  => $inv->quantity,
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
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($so) {
                return [
                    'uuid'          => $so->uuid,
                    'public_id'     => $so->public_id,
                    'status'        => $so->status,
                    'supplier_name' => $so->supplier?->name,
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
            ->selectRaw('pallet_warehouses.name, SUM(pallet_inventories.quantity * pallet_inventories.unit_cost) as value')
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
