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
        | Internal Billing API Routes
        |--------------------------------------------------------------------------
        |
        | Primary internal routes for console.
        */
        $router->prefix(config('pallet.api.routing.internal_prefix', 'int'))->group(
            function ($router) {
                $router->group(
                    ['prefix' => 'v1', 'middleware' => ['fleetbase.protected']],
                    function ($router) {
                        $router->fleetbaseRoutes('audits');
                        $router->fleetbaseRoutes('batches');
                        $router->fleetbaseRoutes('inventories');
                        $router->fleetbaseRoutes('products', function ($router, $controller) {
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->fleetbaseRoutes('sales-orders', function($router, $controller){
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->fleetbaseRoutes('stock-adjustments');
                        $router->fleetbaseRoutes('suppliers', function($router, $controller){
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                        $router->fleetbaseRoutes('warehouses', function ($router, $controller){
                            $router->delete('bulk-delete', $controller('bulkDelete'));
                        });
                    }
                );
            }
        );
    }
);
