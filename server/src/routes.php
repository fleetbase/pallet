<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix(config('pallet.api.routing.prefix', 'pallet'))->namespace('Fleetbase\Pallet\Http\Controllers')->group(
    function ($router) {
        /*
        |--------------------------------------------------------------------------
        | Consumable Pallet API Routes
        |--------------------------------------------------------------------------
        |
        | End-user API routes that the SDK and customer applications interface with.
        | These authenticate with an organization API credential via `fleetbase.api`,
        | address records by public id, and are versioned independently of the
        | console's internal routes.
        |
        | NOTE: company scoping on these routes comes from PalletFilter, not from
        | Filter::apply()'s queryForPublic() hook — that hook is gated behind
        | Http::isPublicRequest(), which only matches URIs beginning `v1/` and so
        | never fires for Pallet's package-prefixed `pallet/v1/...` routes.
        */
        $router->group(
            ['prefix' => 'v1', 'middleware' => ['fleetbase.api'], 'namespace' => 'Api\v1'],
            function ($router) {
                $router->group(['prefix' => 'products'], function ($router) {
                    $router->post('/', 'ProductController@create');
                    $router->get('/', 'ProductController@query');
                    $router->get('{id}', 'ProductController@find');
                    $router->put('{id}', 'ProductController@update');
                    $router->patch('{id}', 'ProductController@update');
                    $router->delete('{id}', 'ProductController@delete');
                });

                $router->group(['prefix' => 'warehouses'], function ($router) {
                    $router->post('/', 'WarehouseController@create');
                    $router->get('/', 'WarehouseController@query');
                    $router->get('{id}', 'WarehouseController@find');
                    $router->put('{id}', 'WarehouseController@update');
                    $router->patch('{id}', 'WarehouseController@update');
                    $router->delete('{id}', 'WarehouseController@delete');
                });

                $router->group(['prefix' => 'purchase-orders'], function ($router) {
                    $router->post('/', 'PurchaseOrderController@create');
                    $router->get('/', 'PurchaseOrderController@query');
                    $router->get('{id}', 'PurchaseOrderController@find');
                    $router->put('{id}', 'PurchaseOrderController@update');
                    $router->patch('{id}', 'PurchaseOrderController@update');
                    $router->delete('{id}', 'PurchaseOrderController@delete');
                    $router->post('{id}/receive', 'PurchaseOrderController@receive');
                });

                // Read-only: stock levels follow from receipts, fulfilments, transfers
                // and adjustments rather than being set directly.
                $router->group(['prefix' => 'inventory'], function ($router) {
                    $router->get('availability', 'InventoryController@availability');
                    $router->get('/', 'InventoryController@query');
                    $router->get('{id}', 'InventoryController@find');
                });
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Internal API Routes
        |--------------------------------------------------------------------------
        |
        | Primary internal routes for the Fleetbase console.
        */
        $router->prefix(config('pallet.api.routing.internal_prefix', 'int'))->group(
            function ($router) {
                $router->group(
                    ['prefix' => 'v1', 'middleware' => ['fleetbase.protected']],
                    function ($router) {
                        $router->get('search', 'SearchController@search');

                        /*
                        |--------------------------------------------------------------
                        | Audit Trail — Read-Only
                        |--------------------------------------------------------------
                        | Audit entries are immutable and written programmatically by
                        | the system. Only index and show are permitted via the API.
                        | The event-types endpoint returns available filter categories.
                        */
                        // Stock movement ledger — read-only, see StockTransactionController
                        $router->get('stock-transactions', 'StockTransactionController@queryRecord');
                        $router->get('stock-transactions/{id}', 'StockTransactionController@find');

                        $router->get('audits', 'AuditController@index');
                        $router->get('audits/event-types', 'AuditController@eventTypes');
                        $router->get('audits/{id}', 'AuditController@show');

                        /*
                        |--------------------------------------------------------------
                        | Batches
                        |--------------------------------------------------------------
                        */
                        $router->fleetbaseRoutes('batches', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });

                        /*
                        |--------------------------------------------------------------
                        | Inventory
                        |--------------------------------------------------------------
                        */
                        $router->fleetbaseRoutes('inventories', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });

                        /*
                        |--------------------------------------------------------------
                        | Products
                        |--------------------------------------------------------------
                        */
                        $router->fleetbaseRoutes('products', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->get('products/{product}/variants', 'ProductVariantController@index');
                        $router->post('products/{product}/variants', 'ProductVariantController@store');
                        $router->put('products/{product}/variants/{variant}', 'ProductVariantController@update');
                        $router->delete('products/{product}/variants/{variant}', 'ProductVariantController@destroy');

                        /*
                        |--------------------------------------------------------------
                        | Sales Orders + Line Items
                        |--------------------------------------------------------------
                        */
                        $router->fleetbaseRoutes('sales-orders', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        // Fulfill workflow
                        $router->post('sales-orders/{id}/fulfill', 'SalesOrderController@fulfill');
                        // Nested line-item routes for Sales Orders
                        $router->get('sales-orders/{salesOrder}/items', 'SalesOrderItemController@index');
                        $router->post('sales-orders/{salesOrder}/items', 'SalesOrderItemController@store');
                        $router->put('sales-orders/{salesOrder}/items/{item}', 'SalesOrderItemController@update');
                        $router->delete('sales-orders/{salesOrder}/items/{item}', 'SalesOrderItemController@destroy');

                        /*
                        |--------------------------------------------------------------
                        | Purchase Orders + Line Items
                        |--------------------------------------------------------------
                        */
                        $router->fleetbaseRoutes('purchase-orders', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        // Receive workflow
                        $router->post('purchase-orders/{id}/receive', 'PurchaseOrderController@receive');
                        // Nested line-item routes for Purchase Orders
                        $router->get('purchase-orders/{purchaseOrder}/items', 'PurchaseOrderItemController@index');
                        $router->post('purchase-orders/{purchaseOrder}/items', 'PurchaseOrderItemController@store');
                        $router->put('purchase-orders/{purchaseOrder}/items/{item}', 'PurchaseOrderItemController@update');
                        $router->delete('purchase-orders/{purchaseOrder}/items/{item}', 'PurchaseOrderItemController@destroy');

                        /*
                        |--------------------------------------------------------------
                        | Stock Adjustments
                        |--------------------------------------------------------------
                        */
                        $router->fleetbaseRoutes('stock-adjustments');

                        /*
                        |--------------------------------------------------------------
                        | Suppliers
                        |--------------------------------------------------------------
                        */
                        $router->fleetbaseRoutes('suppliers', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });

                        /*
                        |--------------------------------------------------------------
                        | Warehouses
                        |--------------------------------------------------------------
                        */
                        $router->fleetbaseRoutes('warehouses', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->fleetbaseRoutes('warehouse-zones', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->fleetbaseRoutes('bin-locations', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });

                        /*
                        |--------------------------------------------------------------
                        | WMS Operations
                        |--------------------------------------------------------------
                        */
                        $router->fleetbaseRoutes('inventory-reservations', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->post('inventory-reservations/{id}/release', 'InventoryReservationController@release');
                        $router->post('inventory-reservations/{id}/fulfill', 'InventoryReservationController@fulfill');

                        $router->fleetbaseRoutes('waves', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->post('waves/{id}/start', 'WaveController@start');
                        $router->post('waves/{id}/release', 'WaveController@release');
                        $router->post('waves/{id}/complete', 'WaveController@complete');

                        $router->fleetbaseRoutes('pick-lists', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->post('pick-lists/{id}/start', 'PickListController@start');
                        $router->post('pick-lists/{id}/assign', 'PickListController@assign');
                        $router->post('pick-lists/{id}/complete', 'PickListController@complete');
                        $router->fleetbaseRoutes('pick-list-items', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->post('pick-list-items/{id}/picked', 'PickListItemController@markPicked');

                        $router->fleetbaseRoutes('cycle-counts', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->post('cycle-counts/{id}/start', 'CycleCountController@start');
                        $router->post('cycle-counts/{id}/complete', 'CycleCountController@complete');
                        $router->post('cycle-counts/{id}/approve', 'CycleCountController@approve');
                        $router->fleetbaseRoutes('cycle-count-items', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->post('cycle-count-items/{id}/record-count', 'CycleCountItemController@recordCount');

                        $router->fleetbaseRoutes('stock-transfers', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->post('stock-transfers/{id}/approve', 'StockTransferController@approve');
                        $router->post('stock-transfers/{id}/ship', 'StockTransferController@ship');
                        $router->post('stock-transfers/{id}/receive', 'StockTransferController@receive');
                        $router->post('stock-transfers/{id}/cancel', 'StockTransferController@cancel');
                        $router->fleetbaseRoutes('stock-transfer-items', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });

                        /*
                        |--------------------------------------------------------------
                        | Storefront Inventory Integration
                        |--------------------------------------------------------------
                        */
                        $router->prefix('storefront')->group(function ($router) {
                            $router->get('inventory/resolve', 'StorefrontInventoryController@resolve');
                            $router->get('inventory/availability', 'StorefrontInventoryController@availability');
                            $router->post('inventory/availability-batch', 'StorefrontInventoryController@availabilityBatch');
                            $router->post('inventory/link', 'StorefrontInventoryController@link');
                            $router->post('inventory/unlink', 'StorefrontInventoryController@unlink');
                            $router->post('inventory/reserve', 'StorefrontInventoryController@reserve');
                            $router->post('inventory/reserve-batch', 'StorefrontInventoryController@reserveBatch');
                            $router->get('inventory/reservations/context', 'StorefrontInventoryController@contextReservations');
                            $router->post('inventory/reservations/release-context', 'StorefrontInventoryController@releaseContext');
                            $router->post('inventory/reservations/commit-context', 'StorefrontInventoryController@commitContext');
                            $router->post('inventory/reservations/release-batch', 'StorefrontInventoryController@releaseBatch');
                            $router->post('inventory/reservations/commit-batch', 'StorefrontInventoryController@commitBatch');
                            $router->post('inventory/reservations/{id}/release', 'StorefrontInventoryController@release');
                            $router->post('inventory/reservations/{id}/commit', 'StorefrontInventoryController@commit');
                        });

                        /*
                        |--------------------------------------------------------------
                        | Dashboard Metrics
                        |--------------------------------------------------------------
                        | Read-only aggregated data endpoints consumed by the Pallet
                        | dashboard widgets. All are scoped to the authenticated company.
                        */
                        $router->prefix('metrics')->group(function ($router) {
                            $router->get('kpis', 'MetricsController@kpis');
                            $router->get('inventory-health', 'MetricsController@inventoryHealth');
                            $router->get('warehouse-utilization', 'MetricsController@warehouseUtilization');
                            $router->get('stock-movement', 'MetricsController@stockMovement');
                            $router->get('fulfillment-workload', 'MetricsController@fulfillmentWorkload');
                            $router->get('reorder-risk', 'MetricsController@reorderRisk');
                            $router->get('inventory-summary', 'MetricsController@inventorySummary');
                            $router->get('low-stock', 'MetricsController@lowStock');
                            $router->get('po-status', 'MetricsController@poStatus');
                            $router->get('so-status', 'MetricsController@soStatus');
                            $router->get('stock-value', 'MetricsController@stockValue');
                            $router->get('expiring-stock', 'MetricsController@expiringStock');
                            $router->get('top-products', 'MetricsController@topProducts');
                        });
                    }
                );
            }
        );
    }
);
