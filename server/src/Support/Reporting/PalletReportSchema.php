<?php

namespace Fleetbase\Pallet\Support\Reporting;

use Fleetbase\Support\Reporting\Contracts\ReportSchema;
use Fleetbase\Support\Reporting\ReportSchemaRegistry;
use Fleetbase\Support\Reporting\Schema\Column;
use Fleetbase\Support\Reporting\Schema\Table;

/**
 * Pallet's tables, as report data sources.
 *
 * SCREENS.md §G says to use the existing ReportBuilder verbatim and that Pallet's
 * contribution is starter reports rather than new builder UI. The builder was already
 * mounted on the reports screen and rendered correctly — but its data source list read
 * "No results found", because nothing had registered Pallet's tables with the reporting
 * registry. A query builder with no queryable tables cannot produce a report at all, so
 * the screen looked finished and could do nothing.
 *
 * FleetOps, Fliit and Al Rashed all register through the same registry; this follows
 * that pattern rather than inventing one.
 *
 * The tables chosen are the ones §G's six starter reports need: stock on hand by
 * warehouse and stock valuation come from inventories and warehouses, movement by
 * product from stock transactions, receipt accuracy by supplier from purchase orders and
 * suppliers, count variance history from cycle counts, and expiry exposure from
 * inventories.
 */
class PalletReportSchema implements ReportSchema
{
    public function registerReportSchema(ReportSchemaRegistry $registry): void
    {
        $registry->registerTable($this->createProductsTable());
        $registry->registerTable($this->createInventoriesTable());
        $registry->registerTable($this->createWarehousesTable());
        $registry->registerTable($this->createSuppliersTable());
        $registry->registerTable($this->createPurchaseOrdersTable());
        $registry->registerTable($this->createSalesOrdersTable());
        $registry->registerTable($this->createStockTransactionsTable());
        $registry->registerTable($this->createCycleCountsTable());
    }

    protected function createProductsTable(): Table
    {
        return Table::make('pallet_products')
            ->label('Products')
            ->description('Catalog of stocked products')
            ->category('Catalog')
            ->extension('pallet')
            ->excludeColumns(['uuid', 'deleted_at', 'meta'])
            ->maxRows(50000)
            ->cacheTtl(3600)
            ->columns([
                Column::make('public_id', 'string')->label('Product ID')->searchable()->filterable()->sortable(),
                Column::make('name', 'string')->label('Name')->searchable()->filterable()->sortable(),
                Column::make('sku', 'string')->label('SKU')->searchable()->filterable()->sortable(),
                Column::make('barcode', 'string')->label('Barcode')->searchable()->filterable(),
                Column::make('status', 'string')->label('Status')->filterable()->sortable(),
                Column::make('price', 'decimal')->label('Price')->filterable()->sortable(),
                Column::make('currency', 'string')->label('Currency')->filterable(),
                Column::make('created_at', 'datetime')->label('Created')->filterable()->sortable(),
            ]);
    }

    protected function createInventoriesTable(): Table
    {
        return Table::make('pallet_inventories')
            ->label('Inventory')
            ->description('Stock on hand by product, warehouse and batch')
            ->category('Inventory')
            ->extension('pallet')
            ->excludeColumns(['uuid', 'deleted_at', 'meta'])
            ->maxRows(100000)
            ->cacheTtl(900)
            ->columns([
                Column::make('public_id', 'string')->label('Inventory ID')->searchable()->filterable()->sortable(),
                Column::make('quantity', 'integer')->label('Quantity')->filterable()->sortable(),
                Column::make('reserved_quantity', 'integer')->label('Reserved')->filterable()->sortable(),
                Column::make('available_quantity', 'integer')->label('Available')->filterable()->sortable(),
                Column::make('min_quantity', 'integer')->label('Reorder Point')->filterable()->sortable(),
                Column::make('batch_number', 'string')->label('Batch')->searchable()->filterable(),
                Column::make('lot_number', 'string')->label('Lot')->searchable()->filterable(),
                // Expiry exposure is one of §G's starter reports, so this has to be
                // filterable and sortable rather than merely present.
                Column::make('expiry_date_at', 'datetime')->label('Expires')->filterable()->sortable(),
                Column::make('status', 'string')->label('Status')->filterable()->sortable(),
                Column::make('created_at', 'datetime')->label('Created')->filterable()->sortable(),
            ]);
    }

    protected function createWarehousesTable(): Table
    {
        return Table::make('pallet_warehouses')
            ->label('Warehouses')
            ->description('Storage facilities')
            ->category('Facilities')
            ->extension('pallet')
            ->excludeColumns(['uuid', 'deleted_at', 'meta'])
            ->maxRows(10000)
            ->columns([
                Column::make('public_id', 'string')->label('Warehouse ID')->searchable()->filterable()->sortable(),
                Column::make('name', 'string')->label('Name')->searchable()->filterable()->sortable(),
                Column::make('code', 'string')->label('Code')->searchable()->filterable()->sortable(),
                Column::make('type', 'string')->label('Type')->filterable()->sortable(),
                Column::make('capacity', 'integer')->label('Capacity')->filterable()->sortable(),
                Column::make('status', 'string')->label('Status')->filterable()->sortable(),
            ]);
    }

    protected function createSuppliersTable(): Table
    {
        return Table::make('pallet_suppliers')
            ->label('Suppliers')
            ->description('Goods suppliers')
            ->category('Catalog')
            ->extension('pallet')
            ->excludeColumns(['uuid', 'deleted_at', 'meta'])
            ->maxRows(10000)
            ->columns([
                Column::make('public_id', 'string')->label('Supplier ID')->searchable()->filterable()->sortable(),
                Column::make('name', 'string')->label('Name')->searchable()->filterable()->sortable(),
                Column::make('code', 'string')->label('Code')->searchable()->filterable(),
                Column::make('status', 'string')->label('Status')->filterable()->sortable(),
                Column::make('created_at', 'datetime')->label('Created')->filterable()->sortable(),
            ]);
    }

    protected function createPurchaseOrdersTable(): Table
    {
        return Table::make('pallet_purchase_orders')
            ->label('Purchase Orders')
            ->description('Inbound orders from suppliers')
            ->category('Orders')
            ->extension('pallet')
            ->excludeColumns(['uuid', 'deleted_at', 'meta'])
            ->maxRows(50000)
            ->columns([
                Column::make('public_id', 'string')->label('PO ID')->searchable()->filterable()->sortable(),
                Column::make('order_number', 'string')->label('PO Number')->searchable()->filterable()->sortable(),
                Column::make('status', 'string')->label('Status')->filterable()->sortable(),
                Column::make('currency', 'string')->label('Currency')->filterable(),
                Column::make('order_date_at', 'datetime')->label('Ordered')->filterable()->sortable(),
                Column::make('expected_delivery_at', 'datetime')->label('Expected')->filterable()->sortable(),
                Column::make('created_at', 'datetime')->label('Created')->filterable()->sortable(),
            ]);
    }

    protected function createSalesOrdersTable(): Table
    {
        return Table::make('pallet_sales_orders')
            ->label('Sales Orders')
            ->description('Outbound orders to customers')
            ->category('Orders')
            ->extension('pallet')
            ->excludeColumns(['uuid', 'deleted_at', 'meta'])
            ->maxRows(50000)
            ->columns([
                Column::make('public_id', 'string')->label('SO ID')->searchable()->filterable()->sortable(),
                Column::make('order_number', 'string')->label('SO Number')->searchable()->filterable()->sortable(),
                Column::make('status', 'string')->label('Status')->filterable()->sortable(),
                Column::make('currency', 'string')->label('Currency')->filterable(),
                Column::make('order_date_at', 'datetime')->label('Ordered')->filterable()->sortable(),
                Column::make('expected_delivery_at', 'datetime')->label('Expected')->filterable()->sortable(),
                Column::make('created_at', 'datetime')->label('Created')->filterable()->sortable(),
            ]);
    }

    protected function createStockTransactionsTable(): Table
    {
        return Table::make('pallet_stock_transactions')
            ->label('Stock Movements')
            ->description('Every recorded movement of stock')
            ->category('Inventory')
            ->extension('pallet')
            ->excludeColumns(['uuid', 'deleted_at', 'meta'])
            ->maxRows(200000)
            ->cacheTtl(900)
            ->columns([
                Column::make('public_id', 'string')->label('Movement ID')->searchable()->filterable()->sortable(),
                Column::make('transaction_type', 'string')->label('Type')->filterable()->sortable(),
                Column::make('quantity', 'integer')->label('Quantity')->filterable()->sortable(),
                Column::make('balance_after', 'integer')->label('Balance After')->filterable()->sortable(),
                Column::make('transaction_date_at', 'datetime')->label('Date')->filterable()->sortable(),
                Column::make('created_at', 'datetime')->label('Recorded')->filterable()->sortable(),
            ]);
    }

    protected function createCycleCountsTable(): Table
    {
        return Table::make('pallet_cycle_counts')
            ->label('Cycle Counts')
            ->description('Counts and their variance')
            ->category('Operations')
            ->extension('pallet')
            ->excludeColumns(['uuid', 'deleted_at', 'meta'])
            ->maxRows(50000)
            ->columns([
                Column::make('public_id', 'string')->label('Count ID')->searchable()->filterable()->sortable(),
                Column::make('count_number', 'string')->label('Count Number')->searchable()->filterable()->sortable(),
                Column::make('type', 'string')->label('Type')->filterable()->sortable(),
                Column::make('status', 'string')->label('Status')->filterable()->sortable(),
                Column::make('scheduled_at', 'datetime')->label('Scheduled')->filterable()->sortable(),
                Column::make('completed_at', 'datetime')->label('Completed')->filterable()->sortable(),
            ]);
    }
}
