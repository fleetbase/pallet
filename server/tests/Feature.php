<?php

use Fleetbase\Pallet\Models\BinLocation;
use Fleetbase\Pallet\Models\CycleCount;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\PickList;
use Fleetbase\Pallet\Models\PickListItem;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Fleetbase\Pallet\Models\StockTransfer;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseZone;
use Fleetbase\Pallet\Models\Wave;

test('pallet products are first class product records', function () {
    expect((new Product())->getTable())->toBe('pallet_products');
    expect((new ProductVariant())->getTable())->toBe('pallet_product_variants');
    expect((new Product())->variants()->getForeignKeyName())->toBe('product_uuid');
    expect((new Product())->variants()->getLocalKeyName())->toBe('uuid');
});

test('inventory core relationships resolve by uuid owner keys', function () {
    $inventory = new Inventory();

    expect($inventory->product()->getOwnerKeyName())->toBe('uuid');
    expect($inventory->variant()->getOwnerKeyName())->toBe('uuid');
    expect($inventory->warehouse()->getOwnerKeyName())->toBe('uuid');
    expect($inventory->batch()->getOwnerKeyName())->toBe('uuid');
    expect($inventory->binLocation()->getOwnerKeyName())->toBe('uuid');
    expect($inventory->zone()->getOwnerKeyName())->toBe('uuid');
});

test('wms aggregate relationships resolve by uuid local keys', function () {
    $warehouse = new Warehouse();
    $zone      = new WarehouseZone();
    $wave      = new Wave();
    $pickList  = new PickList();

    expect($warehouse->zones()->getLocalKeyName())->toBe('uuid');
    expect($warehouse->binLocations()->getLocalKeyName())->toBe('uuid');
    expect($zone->binLocations()->getLocalKeyName())->toBe('uuid');
    expect($wave->pickLists()->getLocalKeyName())->toBe('uuid');
    expect($pickList->items()->getLocalKeyName())->toBe('uuid');
});

test('wms item relationships resolve by uuid owner keys', function () {
    $reservation = new InventoryReservation();
    $pickItem    = new PickListItem();
    $cycleCount  = new CycleCount();
    $binLocation = new BinLocation();
    $transfer    = new StockTransfer();

    expect($reservation->inventory()->getOwnerKeyName())->toBe('uuid');
    expect($reservation->warehouse()->getOwnerKeyName())->toBe('uuid');
    expect($pickItem->inventory()->getOwnerKeyName())->toBe('uuid');
    expect($pickItem->binLocation()->getOwnerKeyName())->toBe('uuid');
    expect($cycleCount->items()->getLocalKeyName())->toBe('uuid');
    expect($binLocation->inventoryItems()->getLocalKeyName())->toBe('uuid');
    expect($transfer->items()->getLocalKeyName())->toBe('uuid');
});
