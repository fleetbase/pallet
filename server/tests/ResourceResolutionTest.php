<?php

use Fleetbase\Support\Find;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * Fleetbase resolves a controller's resource class by convention, and for an internal
 * request it looks in this order:
 *
 *   Http\Resources\Internal\v1\{Model}   <- the console's shape
 *   Http\Resources\v1\{Model}            <- the consumable API's shape
 *   Http\Resources\{Model}
 *
 * Pallet's internal resources originally sat in the unversioned namespace and were
 * only ever reached by the third fallback. Adding the consumable API's v1 resources
 * therefore hijacked every internal listing that had a v1 twin: the console started
 * receiving `object`, no `uuid` and no `public_id`, and Ember Data refused the payload
 * with "You must include an 'id' for pallet-product". The products screen rendered
 * blank. Nothing in the suite noticed, because no test asserted which class serves a
 * given request.
 */
function internalRequestFor(string $uri): Request
{
    $request = Request::create('/pallet/int/v1/' . $uri, 'GET');
    $request->setLaravelSession(app('session.store'));

    $route = new Route(['GET'], 'pallet/int/v1/' . $uri, ['namespace' => '\\Fleetbase\\Pallet']);
    $request->setRouteResolver(fn () => $route);
    app()->instance('request', $request);

    return $request;
}

/**
 * Every consumable resource, which is the side that can hijack the console.
 *
 * Enumerating from Internal\v1 instead — the obvious-looking direction — cannot see
 * the failure at all: a consumable resource with no internal twin is simply absent
 * from that list and gets skipped. That is exactly how the suppliers screen broke
 * after this test was already passing. Pallet never had an internal Supplier
 * resource, resolution had always fallen through to the generic FleetbaseResource,
 * and adding Http\Resources\v1\Supplier put a class in front of that fallback.
 */
function consumableResources(): array
{
    $resources = [];

    foreach (glob(__DIR__ . '/../src/Http/Resources/v1/*.php') as $file) {
        $name = basename($file, '.php');

        if ($name === 'DeletedResource') {
            continue;
        }

        $resources[] = $name;
    }

    return $resources;
}

test('every consumable resource has an internal twin', function () {
    $resources = consumableResources();

    expect($resources)->not->toBeEmpty();

    foreach ($resources as $name) {
        $internal = __DIR__ . '/../src/Http/Resources/Internal/v1/' . $name . '.php';

        expect(file_exists($internal))->toBeTrue(
            $name . ' has a consumable resource but no Internal\\v1 twin, so internal requests will resolve the consumable shape and Ember Data will reject the payload'
        );
    }
});

/*
 * NOTE: there is no in-process test of the resolver itself. Find::httpResourceForModel
 * memoises on `model|namespace|version` and does NOT include whether the request is
 * internal, so within one PHP process the first caller wins: run the consumable API
 * tests first and every later internal resolution returns the consumable class from
 * cache. That is a real hazard on a long-lived Octane worker, not a test artifact, and
 * it is logged for Ron rather than worked around here.
 *
 * What these tests pin instead is the layout that makes a cold resolution correct.
 */
test('the internal resource exposes the identifiers Ember Data needs', function () {
    foreach (consumableResources() as $name) {
        $source = file_get_contents(__DIR__ . '/../src/Http/Resources/Internal/v1/' . $name . '.php');

        // A resource that does not override toArray() inherits FleetbaseResource's
        // pass-through, which emits the model's attributes wholesale — uuid and
        // public_id included. Only an explicit toArray() can drop them, so only that
        // case needs checking.
        if (!str_contains($source, 'function toArray')) {
            continue;
        }

        expect(str_contains($source, "'uuid'"))->toBeTrue($name . ' internal resource overrides toArray but does not emit uuid')
            ->and(str_contains($source, "'public_id'"))->toBeTrue($name . ' internal resource overrides toArray but does not emit public_id');
    }
});

test('the consumable resource still withholds them', function () {
    foreach (consumableResources() as $name) {
        $source = file_get_contents(__DIR__ . '/../src/Http/Resources/v1/' . $name . '.php');

        expect(str_contains($source, "'uuid'          =>") || str_contains($source, "'uuid' =>"))->toBeFalse(
            $name . ' consumable resource must not emit a uuid'
        );
    }
});
