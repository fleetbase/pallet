<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

/**
 * Company scoping is the one invariant that cannot be left to a per-model decision:
 * a listing that forgets it serves other tenants' records.
 *
 * These tests assert the invariant against the query that is actually built, not
 * against the presence of a method — a filter can declare queryForPublic() and still
 * never have it called, which is exactly what happens on Pallet's consumable routes
 * (Http::isPublicRequest() only matches URIs starting with "v1/", and Pallet's live
 * under the "pallet/" package prefix).
 */

/**
 * Every model reachable through the API that carries a company_uuid.
 */
function tenantOwnedPalletModels(): array
{
    $models = [];

    foreach (glob(__DIR__ . '/../src/Models/*.php') as $file) {
        $name     = basename($file, '.php');
        $class    = 'Fleetbase\\Pallet\\Models\\' . $name;
        $contents = file_get_contents($file);

        if (!str_contains($contents, 'use HasApiModelBehavior;')) {
            continue;
        }

        if (!class_exists($class)) {
            continue;
        }

        $instance = new $class();

        if (!$instance->isColumn('company_uuid')) {
            continue;
        }

        $models[$name] = $class;
    }

    return $models;
}

function requestOnRoute(string $uri): Request
{
    $request = Request::create('/' . $uri, 'GET');
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('company', 'company-under-test');

    $route = new Route(['GET'], $uri, ['namespace' => '\\Fleetbase\\Pallet']);
    $request->setRouteResolver(fn () => $route);

    return $request;
}

/**
 * The compiled SQL for a model's listing, with filters applied the same way
 * HasApiModelBehavior::searchBuilder() applies them.
 */
function filteredSqlFor(string $class, string $uri): string
{
    $model   = new $class();
    $builder = $model->applyCustomFilters(requestOnRoute($uri), $class::query());

    return $builder->toSql();
}

test('every tenant-owned model resolves a filter class', function () {
    $models = tenantOwnedPalletModels();

    expect($models)->not->toBeEmpty();

    foreach ($models as $name => $class) {
        $filter = 'Fleetbase\\Pallet\\Http\\Filter\\' . $name . 'Filter';

        expect(class_exists($filter))->toBeTrue(
            $name . ' is tenant-owned and API-exposed but has no ' . $name . 'Filter, so its listing carries no company clause'
        );
    }
});

test('every tenant-owned listing carries a company clause on the internal API', function () {
    foreach (tenantOwnedPalletModels() as $name => $class) {
        $sql = filteredSqlFor($class, 'pallet/int/v1/' . Str::plural(Str::kebab($name)));

        expect(str_contains($sql, 'company_uuid'))->toBeTrue($name . ' internal listing is not scoped to a company');
    }
});

test('every tenant-owned listing carries a company clause on the consumable API', function () {
    foreach (tenantOwnedPalletModels() as $name => $class) {
        $sql = filteredSqlFor($class, 'pallet/v1/' . Str::plural(Str::kebab($name)));

        expect(str_contains($sql, 'company_uuid'))->toBeTrue($name . ' public listing is not scoped to a company');
    }
});

test('the company clause is table-qualified so joined listings stay unambiguous', function () {
    foreach (tenantOwnedPalletModels() as $name => $class) {
        $table = (new $class())->getTable();
        $sql   = filteredSqlFor($class, 'pallet/v1/' . Str::plural(Str::kebab($name)));

        expect(str_contains($sql, $table . '"."company_uuid'))->toBeTrue($name . ' company clause is not qualified with ' . $table);
    }
});

test('a listing returns nothing when no company is in session rather than everything', function () {
    $request = Request::create('/pallet/v1/purchase-orders', 'GET');
    $request->setLaravelSession(app('session.store'));
    $route = new Route(['GET'], 'pallet/v1/purchase-orders', ['namespace' => '\\Fleetbase\\Pallet']);
    $request->setRouteResolver(fn () => $route);

    $companyA = (string) Str::uuid();
    session(['company' => $companyA]);
    Fleetbase\Pallet\Models\PurchaseOrder::create(['company_uuid' => $companyA, 'status' => 'pending']);

    $request->session()->forget('company');

    $rows = (new Fleetbase\Pallet\Models\PurchaseOrder())
        ->applyCustomFilters($request, Fleetbase\Pallet\Models\PurchaseOrder::query())
        ->get();

    expect($rows)->toHaveCount(0);
});
