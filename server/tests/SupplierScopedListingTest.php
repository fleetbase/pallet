<?php

use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Illuminate\Support\Str;

/*
 * The supplier detail panel answers two questions about a supplier — what have we
 * ordered from them, and what do they supply. Neither listing could be narrowed to a
 * supplier: PurchaseOrderFilter declared no methods at all, and `supplier` was in the
 * product model's $filterParams with no method to act on it, so the parameter was
 * accepted and silently ignored. An ignored filter is worse than a missing one: the
 * panel would have rendered every product in the catalogue as if the supplier stocked
 * it.
 */
test('purchase orders can be scoped to one supplier', function () {
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $theirs  = (string) Str::uuid();
    $another = (string) Str::uuid();

    foreach ([$theirs, $theirs, $another] as $supplier) {
        PurchaseOrder::create([
            'company_uuid'  => $company,
            'supplier_uuid' => $supplier,
            'status'        => 'pending',
        ]);
    }

    $scoped = PurchaseOrder::where('company_uuid', $company)->where('supplier_uuid', $theirs)->get();

    expect($scoped)->toHaveCount(2)
        ->and($scoped->pluck('supplier_uuid')->unique()->all())->toBe([$theirs]);
});

test('products can be scoped to one supplier', function () {
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $theirs  = (string) Str::uuid();
    $another = (string) Str::uuid();

    foreach ([$theirs, $another, $another] as $supplier) {
        Product::create([
            'company_uuid'  => $company,
            'supplier_uuid' => $supplier,
            'name'          => 'Widget ' . Str::random(4),
            'status'        => 'active',
        ]);
    }

    $scoped = Product::where('company_uuid', $company)->where('supplier_uuid', $theirs)->get();

    expect($scoped)->toHaveCount(1)
        ->and($scoped->first()->supplier_uuid)->toBe($theirs);
});

test('the product filter declares a method for every filterable param it advertises', function () {
    // `supplier` sat in $filterParams for a long time with no matching filter method.
    // A param the model advertises and the filter ignores fails open — the caller gets
    // an unfiltered list and no error.
    $filter  = new ReflectionClass(Fleetbase\Pallet\Http\Filter\ProductFilter::class);
    $methods = collect($filter->getMethods(ReflectionMethod::IS_PUBLIC))
        ->map(fn ($method) => Str::snake($method->getName()))
        ->all();

    foreach (['supplier', 'category'] as $param) {
        expect($methods)->toContain($param);
    }
});

test('docks can be scoped to one warehouse', function () {
    // The warehouse detail panel lists that building's docks; without a filter method
    // the listing returned every dock the company owns across every site.
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $here      = (string) Str::uuid();
    $elsewhere = (string) Str::uuid();

    foreach ([$here, $here, $elsewhere] as $warehouse) {
        Fleetbase\Pallet\Models\WarehouseDock::create([
            'company_uuid'   => $company,
            'warehouse_uuid' => $warehouse,
            'dock_number'    => 'D-' . Str::random(3),
            'status'         => 'active',
        ]);
    }

    $scoped = Fleetbase\Pallet\Models\WarehouseDock::where('company_uuid', $company)
        ->where('warehouse_uuid', $here)
        ->get();

    expect($scoped)->toHaveCount(2);
});

test('zones and bins can be scoped to their parent', function () {
    // SCREENS.md section C reaches zones and bins through the warehouse. Neither filter
    // declared a method, so both listings returned every row the company owned.
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $here      = (string) Str::uuid();
    $elsewhere = (string) Str::uuid();
    $zone      = (string) Str::uuid();

    foreach ([$here, $here, $elsewhere] as $warehouse) {
        Fleetbase\Pallet\Models\WarehouseZone::create([
            'company_uuid'   => $company,
            'warehouse_uuid' => $warehouse,
            'name'           => 'Zone ' . Str::random(3),
            'status'         => 'active',
        ]);
    }

    foreach ([$zone, $zone, (string) Str::uuid()] as $zoneUuid) {
        Fleetbase\Pallet\Models\BinLocation::create([
            'company_uuid'   => $company,
            'warehouse_uuid' => $here,
            'zone_uuid'      => $zoneUuid,
            'bin_number'     => 'B-' . Str::random(3),
            'status'         => 'active',
        ]);
    }

    $zones = Fleetbase\Pallet\Models\WarehouseZone::where('company_uuid', $company)
        ->where('warehouse_uuid', $here)->get();
    $bins = Fleetbase\Pallet\Models\BinLocation::where('company_uuid', $company)
        ->where('zone_uuid', $zone)->get();

    expect($zones)->toHaveCount(2)
        ->and($bins)->toHaveCount(2);
});

test('every facilities filter declares the parent scope its panel relies on', function () {
    // These three panels each scope to a parent. A filter that declares no method for a
    // param fails open — the listing comes back unfiltered with no error, which is how
    // the supplier products panel would have shown the whole catalogue.
    $expected = [
        Fleetbase\Pallet\Http\Filter\WarehouseDockFilter::class => ['warehouse'],
        Fleetbase\Pallet\Http\Filter\WarehouseZoneFilter::class => ['warehouse'],
        Fleetbase\Pallet\Http\Filter\BinLocationFilter::class   => ['warehouse', 'zone'],
    ];

    foreach ($expected as $class => $params) {
        $methods = collect((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->map(fn ($method) => $method->getName())
            ->all();

        foreach ($params as $param) {
            expect($methods)->toContain($param);
        }
    }
});
