<?php

use Fleetbase\Pallet\Http\Resources\WarehouseAisle as AisleResource;
use Fleetbase\Pallet\Http\Resources\WarehouseBin as BinResource;
use Fleetbase\Pallet\Http\Resources\WarehouseDock as DockResource;
use Fleetbase\Pallet\Http\Resources\WarehouseRack as RackResource;
use Fleetbase\Pallet\Http\Resources\WarehouseSection as SectionResource;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseAisle;
use Fleetbase\Pallet\Models\WarehouseBin;
use Fleetbase\Pallet\Models\WarehouseDock;
use Fleetbase\Pallet\Models\WarehouseRack;
use Fleetbase\Pallet\Models\WarehouseSection;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * None of the hierarchy resources emitted their parent foreign key, so a client
 * receiving sections, aisles, racks and bins could not tell which parent each
 * one belonged to and could not reconstruct the hierarchy at all. The dock
 * resource emitted nothing beyond ids and timestamps. The `area` polygons — the
 * geometry the layout designer is built on — were invisible too.
 */
function internalRequest(): Request
{
    $request = Request::create('/pallet/int/v1/warehouses', 'GET');
    $route   = new Route(['GET'], 'pallet/int/v1/warehouses', ['namespace' => '\\Fleetbase\\Pallet']);
    $request->setRouteResolver(fn () => $route);
    app()->instance('request', $request);

    return $request;
}

function hierarchyFixture(): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Hierarchy WH', 'code' => 'HIER-' . uniqid()]);
    $section   = WarehouseSection::create(['company_uuid' => $company, 'warehouse_uuid' => $warehouse->uuid, 'name' => 'Section A']);
    $aisle     = WarehouseAisle::create(['company_uuid' => $company, 'warehouse_uuid' => $warehouse->uuid, 'section_uuid' => $section->uuid, 'aisle_number' => 'A1']);
    $rack      = WarehouseRack::create(['company_uuid' => $company, 'warehouse_uuid' => $warehouse->uuid, 'aisle_uuid' => $aisle->uuid, 'rack_number' => 'R1']);
    $bin       = WarehouseBin::create(['company_uuid' => $company, 'warehouse_uuid' => $warehouse->uuid, 'rack_uuid' => $rack->uuid, 'bin_number' => 'B1']);
    $dock      = WarehouseDock::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'dock_number'    => 'D1',
        'type'           => 'inbound',
        'direction'      => 'in',
        'status'         => 'available',
        'capacity'       => 2,
    ]);

    return compact('warehouse', 'section', 'aisle', 'rack', 'bin', 'dock');
}

test('every hierarchy level reports the parent it belongs to', function () {
    $f       = hierarchyFixture();
    $request = internalRequest();

    expect((new SectionResource($f['section']))->toArray($request)['warehouse_uuid'])->toBe($f['warehouse']->uuid)
        ->and((new AisleResource($f['aisle']))->toArray($request)['section_uuid'])->toBe($f['section']->uuid)
        ->and((new RackResource($f['rack']))->toArray($request)['aisle_uuid'])->toBe($f['aisle']->uuid)
        ->and((new BinResource($f['bin']))->toArray($request)['rack_uuid'])->toBe($f['rack']->uuid);
});

test('every hierarchy level reports the warehouse it belongs to', function () {
    $f        = hierarchyFixture();
    $request  = internalRequest();
    $expected = $f['warehouse']->uuid;

    foreach ([
        [new SectionResource($f['section'])],
        [new AisleResource($f['aisle'])],
        [new RackResource($f['rack'])],
        [new BinResource($f['bin'])],
        [new DockResource($f['dock'])],
    ] as [$resource]) {
        expect($resource->toArray($request)['warehouse_uuid'])->toBe($expected);
    }
});

test('the dock resource exposes the dock, not just its id', function () {
    $f       = hierarchyFixture();
    $payload = (new DockResource($f['dock']))->toArray(internalRequest());

    expect($payload['dock_number'])->toBe('D1')
        ->and($payload['type'])->toBe('inbound')
        ->and($payload['direction'])->toBe('in')
        ->and($payload['status'])->toBe('available')
        ->and((int) $payload['capacity'])->toBe(2);
});

test('sections and aisles expose the area geometry the layout designer needs', function () {
    $f       = hierarchyFixture();
    $request = internalRequest();

    expect((new SectionResource($f['section']))->toArray($request))->toHaveKey('area')
        ->and((new AisleResource($f['aisle']))->toArray($request))->toHaveKey('area');
});
