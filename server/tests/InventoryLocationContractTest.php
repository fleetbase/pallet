<?php

use Fleetbase\Pallet\Http\Resources\Internal\v1\Inventory as InventoryResource;
use Fleetbase\Pallet\Models\BinLocation;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseZone;
use Illuminate\Http\Request;

/**
 * Zone and bin location were write-only: the form saved zone_uuid and
 * bin_location_uuid, but the resource emitted only those uuids, so no screen
 * could ever say where in the warehouse the stock actually sits.
 */
function inventoryWithLocation(): Inventory
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Loc WH', 'code' => 'LOC-' . uniqid()]);
    $zone      = WarehouseZone::create(['company_uuid' => $company, 'warehouse_uuid' => $warehouse->uuid, 'name' => 'Cold Zone']);
    $bin       = BinLocation::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'zone_uuid'      => $zone->uuid,
        'bin_number'     => 'A-01-01',
    ]);
    $product = Product::create(['company_uuid' => $company, 'name' => 'Loc Product', 'sku' => 'LOC-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'      => $company,
        'warehouse_uuid'    => $warehouse->uuid,
        'product_uuid'      => $product->uuid,
        'zone_uuid'         => $zone->uuid,
        'bin_location_uuid' => $bin->uuid,
        'status'            => 'active',
        'quantity'          => 5,
    ]);

    return Inventory::with(['zone', 'binLocation'])->where('uuid', $inventory->uuid)->first();
}

test('the inventory resource exposes the zone the stock is held in', function () {
    $payload = (new InventoryResource(inventoryWithLocation()))->toArray(Request::create('/'));

    expect($payload)->toHaveKey('zone');

    $zone = json_decode(json_encode($payload['zone']), true);

    expect($zone['name'])->toBe('Cold Zone');
});

test('the inventory resource exposes the bin location the stock is held in', function () {
    $payload = (new InventoryResource(inventoryWithLocation()))->toArray(Request::create('/'));

    expect($payload)->toHaveKey('bin_location');

    $bin = json_decode(json_encode($payload['bin_location']), true);

    expect($bin['bin_number'])->toBe('A-01-01');
});
