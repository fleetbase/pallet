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
