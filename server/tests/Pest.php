<?php

use Fleetbase\Pallet\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

uses(TestCase::class)->in(__DIR__);

/**
 * A request shaped like one arriving on the consumable API.
 *
 * The controller has to be bound onto the route: HasApiModelBehavior consults
 * authorization directives on every listing, and resolving those reflects on the
 * route's controller.
 */
function publicApiRequest(string $uri, string $method = 'GET', array $payload = [], ?string $controller = null): Request
{
    $request = Request::create('/pallet/v1/' . $uri, $method, $payload);
    $request->setLaravelSession(app('session.store'));

    $action = ['namespace' => '\\Fleetbase\\Pallet'];

    if ($controller) {
        $action['controller'] = $controller . '@query';
    }

    $route = new Route([$method], 'pallet/v1/' . $uri, $action);

    if ($controller) {
        $route->controller = app($controller);
    }

    $request->setRouteResolver(fn () => $route);

    return $request;
}

/**
 * The consumable API authenticates with an API credential rather than a user session.
 */
function asCompany(string $company): void
{
    session(['company' => $company, 'api_credential' => 'test-credential']);
}

function resourceArray($resource, Request $request): array
{
    return json_decode(json_encode($resource->toArray($request)), true);
}

/**
 * Every uuid-shaped key anywhere in a consumable payload, at any depth.
 *
 * The per-resource tests originally checked only top-level keys. That is why the
 * audit resource could leak product_uuid, warehouse_uuid, inventory_uuid and more
 * inside old_values/new_values/meta while the suite stayed green — the Postman
 * collection's runtime check, which regexes the whole serialised body, is what
 * actually caught it.
 */
function internalIdKeysIn($value, string $path = ''): array
{
    if (is_object($value)) {
        $value = (array) $value;
    }

    if (!is_array($value)) {
        return [];
    }

    $found = [];

    foreach ($value as $key => $item) {
        $here = $path === '' ? (string) $key : $path . '.' . $key;

        if (is_string($key) && (str_ends_with($key, '_uuid') || $key === 'uuid')) {
            $found[] = $here;
        }

        $found = array_merge($found, internalIdKeysIn($item, $here));
    }

    return $found;
}
