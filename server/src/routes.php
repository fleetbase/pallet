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
                        /*
                        |--------------------------------------------------------------
                        | Audit Trail — Read-Only
                        |--------------------------------------------------------------
                        | Audit entries are immutable and written programmatically by
                        | the system. Only index and show are permitted via the API.
                        | The event-types endpoint returns available filter categories.
                        */
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
                    }
                );
            }
        );
    }
);
