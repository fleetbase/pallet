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
 * Every model that has both an internal and a consumable resource.
 */
function modelsWithBothResources(): array
{
    $both = [];

    foreach (glob(__DIR__ . '/../src/Http/Resources/Internal/v1/*.php') as $file) {
        $name = basename($file, '.php');

        if (!file_exists(__DIR__ . '/../src/Http/Resources/v1/' . $name . '.php')) {
            continue;
        }

        $model = 'Fleetbase\\Pallet\\Models\\' . $name;

        if (class_exists($model)) {
            $both[$name] = $model;
        }
    }

    return $both;
}

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
    foreach (modelsWithBothResources() as $name => $model) {
        $source = file_get_contents(__DIR__ . '/../src/Http/Resources/Internal/v1/' . $name . '.php');

        expect(str_contains($source, "'uuid'"))->toBeTrue($name . ' internal resource must emit uuid')
            ->and(str_contains($source, "'public_id'"))->toBeTrue($name . ' internal resource must emit public_id');
    }
});

test('the consumable resource still withholds them', function () {
    foreach (modelsWithBothResources() as $name => $_) {
        $source = file_get_contents(__DIR__ . '/../src/Http/Resources/v1/' . $name . '.php');

        expect(str_contains($source, "'uuid'          =>") || str_contains($source, "'uuid' =>"))->toBeFalse(
            $name . ' consumable resource must not emit a uuid'
        );
    }
});
