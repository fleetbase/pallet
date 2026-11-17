<?php

/**
 * Every API-exposed model must declare a payloadKey.
 *
 * Without one, getPluralName() falls back to the table name, so responses come
 * back keyed `pallet_stock_transfers` instead of `stock_transfers`. The Ember
 * serializers key off the model name, cannot match the prefixed key, and treat
 * the whole envelope as a single record — which killed all five operations
 * screens (transfers, cycle counts, pick lists, waves, reservations) outright.
 */
$expectedPayloadKeys = [
    'Audit'                => 'audit',
    'Batch'                => 'batch',
    'BinLocation'          => 'bin_location',
    'CycleCount'           => 'cycle_count',
    'CycleCountItem'       => 'cycle_count_item',
    'Inventory'            => 'inventory',
    'InventoryReservation' => 'inventory_reservation',
    'PickList'             => 'pick_list',
    'PickListItem'         => 'pick_list_item',
    'Product'              => 'product',
    'ProductKitComponent'  => 'product_kit_component',
    'ProductVariant'       => 'product_variant',
    'PurchaseOrder'        => 'purchase_order',
    'PurchaseOrderItem'    => 'purchase_order_item',
    'SalesOrder'           => 'sales_order',
    'SalesOrderItem'       => 'sales_order_item',
    'StockAdjustment'      => 'stock_adjustment',
    'StockTransaction'     => 'stock_transaction',
    'StockTransfer'        => 'stock_transfer',
    'StockTransferItem'    => 'stock_transfer_item',
    'Supplier'             => 'supplier',
    'Warehouse'            => 'warehouse',
    'WarehouseAisle'       => 'warehouse_aisle',
    'WarehouseBin'         => 'warehouse_bin',
    'WarehouseDock'        => 'warehouse_dock',
    'WarehouseRack'        => 'warehouse_rack',
    'WarehouseSection'     => 'warehouse_section',
    'WarehouseZone'        => 'warehouse_zone',
    'Wave'                 => 'wave',
];

function payloadKeyOf(string $model): ?string
{
    $class      = 'Fleetbase\\Pallet\\Models\\' . $model;
    $reflection = new ReflectionClass($class);

    if (!$reflection->hasProperty('payloadKey')) {
        return null;
    }

    $property = $reflection->getProperty('payloadKey');
    $property->setAccessible(true);

    return $property->getValue(new $class());
}

test('every model declares the payload key its Ember serializer expects', function () use ($expectedPayloadKeys) {
    foreach ($expectedPayloadKeys as $model => $expected) {
        expect(payloadKeyOf($model))->toBe($expected, $model . ' payload key');
    }
});

test('models exposed through the api resolve an unprefixed plural name', function () use ($expectedPayloadKeys) {
    foreach ($expectedPayloadKeys as $model => $expected) {
        $class    = 'Fleetbase\\Pallet\\Models\\' . $model;
        $instance = new $class();

        if (!method_exists($instance, 'getPluralName')) {
            continue;
        }

        expect($instance->getPluralName())
            ->toBe(Illuminate\Support\Str::plural($expected), $model . ' plural name')
            ->not->toStartWith('pallet_', $model . ' leaks the table prefix');
    }
});

test('payload keys are unique so no two resources collide', function () use ($expectedPayloadKeys) {
    $keys = [];

    foreach (array_keys($expectedPayloadKeys) as $model) {
        $keys[$model] = payloadKeyOf($model);
    }

    expect(count(array_unique($keys)))->toBe(count($keys), 'duplicate payload keys: ' . json_encode($keys));
});
