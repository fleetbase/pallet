<?php

use Fleetbase\FleetOps\Models\Vendor;
use Fleetbase\Pallet\Http\Filter\SupplierFilter;
use Fleetbase\Pallet\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * Pallet suppliers share the core `vendors` table with FleetOps vendors, so the
 * listing has to be narrowed to Pallet's own records.
 *
 * It used to narrow on `type = 'pallet-supplier'` — a sentinel value no code
 * path ever wrote — so every supplier created through the UI was invisible and
 * the list was permanently empty. `type` is also bound to the user-facing
 * "Supplier Type" selector in the form, so it can never be the discriminator.
 */
function listedSuppliers(string $company): array
{
    $request = Request::create('/pallet/int/v1/suppliers', 'GET');
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('company', $company);

    // apply() only narrows internal requests, and internal-ness is read off the
    // bound route — so the request has to carry one.
    $route = new Route(['GET'], 'pallet/int/v1/suppliers', ['namespace' => '\\Fleetbase\\Pallet']);
    $request->setRouteResolver(fn () => $route);

    return (new SupplierFilter($request))->apply(Supplier::query())->get()->all();
}

function seedSupplierAndVendor(): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $supplier = Supplier::create(['company_uuid' => $company, 'name' => 'Pacific Steel Supply']);
    Vendor::create(['company_uuid' => $company, 'name' => 'A FleetOps Vendor']);

    return [$company, $supplier];
}

test('a created supplier appears in the supplier listing', function () {
    [$company, $supplier] = seedSupplierAndVendor();

    $results = listedSuppliers($company);

    expect($results)->toHaveCount(1)
        ->and($results[0]->uuid)->toBe($supplier->uuid)
        ->and($results[0]->name)->toBe('Pacific Steel Supply');
});

test('the listing excludes FleetOps vendors sharing the vendors table', function () {
    [$company] = seedSupplierAndVendor();

    expect(collect(listedSuppliers($company))->pluck('name')->all())
        ->not->toContain('A FleetOps Vendor');
});

test('suppliers are discriminated by public id prefix, not by the editable type', function () {
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    // `type` is user-editable through the form, so changing it must not hide the record.
    $supplier = Supplier::create([
        'company_uuid' => $company,
        'name'         => 'Retyped Supplier',
        'type'         => 'manufacturer',
    ]);

    expect($supplier->public_id)->toStartWith('supplier_');

    $results = listedSuppliers($company);

    expect($results)->toHaveCount(1)
        ->and($results[0]->uuid)->toBe($supplier->uuid);
});

test('the listing is scoped to the authenticated company', function () {
    [$company]      = seedSupplierAndVendor();
    [$otherCompany] = seedSupplierAndVendor();

    expect(listedSuppliers($company))->toHaveCount(1)
        ->and(listedSuppliers($otherCompany))->toHaveCount(1);
});
