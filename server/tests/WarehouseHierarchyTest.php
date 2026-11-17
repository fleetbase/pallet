<?php

use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseAisle;
use Fleetbase\Pallet\Models\WarehouseBin;
use Fleetbase\Pallet\Models\WarehouseRack;
use Fleetbase\Pallet\Models\WarehouseSection;
use Fleetbase\Pallet\Models\WarehouseZone;

function makeHierarchyFixture(): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Hierarchy WH', 'code' => 'HWH-' . uniqid()]);
    $zone      = WarehouseZone::create(['company_uuid' => $company, 'warehouse_uuid' => $warehouse->uuid, 'name' => 'Zone A', 'code' => 'ZA-' . uniqid()]);
    $section   = WarehouseSection::create(['company_uuid' => $company, 'warehouse_uuid' => $warehouse->uuid, 'name' => 'Section 1']);

    $aisle = WarehouseAisle::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'zone_uuid'      => $zone->uuid,
        'section_uuid'   => $section->uuid,
        'aisle_number'   => 'A1',
    ]);

    $rack = WarehouseRack::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'aisle_uuid'     => $aisle->uuid,
        'rack_number'    => 'R1',
        'capacity'       => 20,
    ]);

    $bin = WarehouseBin::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'rack_uuid'      => $rack->uuid,
        'bin_number'     => 'B1',
    ]);

    return [$warehouse, $zone, $section, $aisle, $rack, $bin];
}

test('a warehouse resolves its full layout hierarchy', function () {
    [$warehouse, $zone, $section, $aisle, $rack] = makeHierarchyFixture();

    $warehouse = $warehouse->fresh();

    expect($warehouse->sections->pluck('uuid'))->toContain($section->uuid)
        ->and($warehouse->aisles->pluck('uuid'))->toContain($aisle->uuid)
        ->and($warehouse->racks->pluck('uuid'))->toContain($rack->uuid)
        ->and($zone->fresh()->aisles->pluck('uuid'))->toContain($aisle->uuid);
});

test('the hierarchy chains parent to child', function () {
    [, , $section, $aisle, $rack, $bin] = makeHierarchyFixture();

    expect($section->fresh()->aisles->pluck('uuid'))->toContain($aisle->uuid)
        ->and($aisle->fresh()->racks->pluck('uuid'))->toContain($rack->uuid)
        ->and($rack->fresh()->bins->pluck('uuid'))->toContain($bin->uuid);
});

test('every hierarchy record gets a distinctly prefixed public id', function () {
    [$warehouse, , $section, $aisle, $rack, $bin] = makeHierarchyFixture();

    expect($section->fresh()->public_id)->toStartWith('warehouse_section_')
        ->and($aisle->fresh()->public_id)->toStartWith('warehouse_aisle_')
        ->and($rack->fresh()->public_id)->toStartWith('warehouse_rack_')
        ->and($bin->fresh()->public_id)->toStartWith('warehouse_bin_');
});

test('a warehouse holding zones and bins can be fetched without recursing', function () {
    [$warehouse, $zone] = makeHierarchyFixture();

    Fleetbase\Pallet\Models\BinLocation::create([
        'company_uuid'   => $warehouse->company_uuid,
        'warehouse_uuid' => $warehouse->uuid,
        'zone_uuid'      => $zone->uuid,
        'bin_number'     => 'BL1',
    ]);

    // Warehouse eager-loads zones; a zone eager-loading its warehouse back
    // recursed until memory was exhausted, taking out every warehouse screen
    $fetched = Warehouse::where('uuid', $warehouse->uuid)->first();

    expect($fetched)->not->toBeNull()
        ->and($fetched->zones)->toHaveCount(1)
        ->and($fetched->binLocations)->toHaveCount(1)
        ->and(Fleetbase\Pallet\Models\BinLocation::where('warehouse_uuid', $warehouse->uuid)->first())->not->toBeNull();
});
