<?php

use Fleetbase\Pallet\Http\Filter\InventoryFilter;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * Every tenant-owned listing must be scoped to the authenticated company.
 * InventoryFilter declared no queryForInternal/queryForPublic at all, so the
 * inventory listing carried no company clause of its own.
 */
function seedInventoryFor(string $company, string $name): Inventory
{
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => $name . ' WH', 'code' => 'TEN-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => $name, 'sku' => 'TEN-' . uniqid()]);

    return Inventory::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'product_uuid'   => $product->uuid,
        'quantity'       => 25,
        'status'         => 'active',
    ]);
}

function listedInventoryFor(string $company): array
{
    $request = Request::create('/pallet/int/v1/inventories', 'GET');
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('company', $company);

    $route = new Route(['GET'], 'pallet/int/v1/inventories', ['namespace' => '\\Fleetbase\\Pallet']);
    $request->setRouteResolver(fn () => $route);

    return (new InventoryFilter($request))->apply(Inventory::query()->summarizeByProduct())->get()->all();
}

test('the inventory listing only returns the authenticated company records', function () {
    $companyA = (string) Illuminate\Support\Str::uuid();
    $companyB = (string) Illuminate\Support\Str::uuid();

    $ours = seedInventoryFor($companyA, 'Our Product');
    seedInventoryFor($companyB, 'Another Tenant Product');

    $results = listedInventoryFor($companyA);

    expect($results)->toHaveCount(1)
        ->and($results[0]->product_uuid)->toBe($ours->product_uuid);
});

test('each company sees only its own inventory', function () {
    $companyA = (string) Illuminate\Support\Str::uuid();
    $companyB = (string) Illuminate\Support\Str::uuid();

    seedInventoryFor($companyA, 'Company A Product');
    $theirs = seedInventoryFor($companyB, 'Company B Product');

    $resultsB = listedInventoryFor($companyB);

    expect($resultsB)->toHaveCount(1)
        ->and($resultsB[0]->product_uuid)->toBe($theirs->product_uuid);
});

/*
 * The structural guard that used to live here checked method_exists() on each filter
 * class. That became meaningless once the filters inherited those methods from
 * PalletFilter — it would pass for a model with no filter class at all, which was the
 * actual defect. TenantScopeContractTest replaces it: it enumerates every tenant-owned
 * model and asserts a company clause in the SQL that gets built, for both the internal
 * and the consumable route shapes.
 */
