<?php

/**
 * Documentation that drifts is worse than none: a consumer trusts it and builds
 * against an endpoint that moved or never existed.
 *
 * These tests compare the README's endpoint table against the routes actually
 * registered, in both directions — an undocumented route and a documented route
 * that no longer exists are both failures.
 */
function readmeLines(): array
{
    return explode("\n", file_get_contents(__DIR__ . '/../../README.md'));
}

function registeredPublicRoutes(): array
{
    $routes = [];

    foreach (app('router')->getRoutes()->getRoutes() as $route) {
        if (!str_starts_with($route->uri(), 'pallet/v1/')) {
            continue;
        }

        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            $routes[] = ['method' => $method, 'uri' => $route->uri()];
        }
    }

    return $routes;
}

/**
 * The rows of the README endpoint table, as method/uri pairs.
 */
function documentedPublicRoutes(): array
{
    $documented = [];

    foreach (readmeLines() as $line) {
        if (!preg_match('/^\|\s*(`[A-Z`\s]+`)\s*\|\s*`(pallet\/v1\/[^`]+)`\s*\|/', $line, $matches)) {
            continue;
        }

        foreach (preg_split('/\s+/', trim(str_replace('`', ' ', $matches[1]))) as $method) {
            if ($method !== '') {
                $documented[] = ['method' => $method, 'uri' => $matches[2]];
            }
        }
    }

    return $documented;
}

test('every consumable route is documented in the README', function () {
    $documented = array_map(fn ($row) => $row['method'] . ' ' . $row['uri'], documentedPublicRoutes());

    expect(registeredPublicRoutes())->not->toBeEmpty();

    foreach (registeredPublicRoutes() as $route) {
        $signature = $route['method'] . ' ' . $route['uri'];

        expect(in_array($signature, $documented, true))->toBeTrue(
            $signature . ' is registered but missing from the README endpoint table'
        );
    }
});

test('the README documents no route that does not exist', function () {
    $registered = array_map(fn ($row) => $row['method'] . ' ' . $row['uri'], registeredPublicRoutes());

    expect(documentedPublicRoutes())->not->toBeEmpty();

    foreach (documentedPublicRoutes() as $route) {
        $signature = $route['method'] . ' ' . $route['uri'];

        expect(in_array($signature, $registered, true))->toBeTrue(
            $signature . ' is documented in the README but is not a registered route'
        );
    }
});

test('the README does not promise writes the API refuses', function () {
    $documented = array_map(fn ($row) => $row['method'] . ' ' . $row['uri'], documentedPublicRoutes());

    $refused = [
        'POST pallet/v1/inventory'                => 'stock levels follow from operations, not direct writes',
        'PUT pallet/v1/inventory/{id}'            => 'stock levels follow from operations, not direct writes',
        'DELETE pallet/v1/inventory/{id}'         => 'stock levels follow from operations, not direct writes',
        'PUT pallet/v1/stock-adjustments/{id}'    => 'correcting an adjustment means making another',
        'DELETE pallet/v1/stock-adjustments/{id}' => 'correcting an adjustment means making another',
        'POST pallet/v1/batches'                  => 'a batch is produced by receiving stock',
        'POST pallet/v1/audits'                   => 'audit entries are written by the system',
        'PUT pallet/v1/stock-transfers/{id}'      => 'a settable status would move the record without moving the stock',
    ];

    foreach ($refused as $signature => $why) {
        expect(in_array($signature, $documented, true))->toBeFalse($signature . ' must not be documented — ' . $why);
    }
});

/*
 * The middleware behind these routes is called AuthenticateOnceWithBasicAuth but reads
 * $request->bearerToken(), which only matches "Authorization: Bearer". A curl example
 * using -u would send basic auth and 401 — this documentation said exactly that until
 * e402d991, so it is worth pinning rather than trusting the class name.
 */
test('the README documents bearer auth, not basic auth', function () {
    $readme = implode("\n", readmeLines());

    expect($readme)->toContain('Authorization: Bearer $FLEETBASE_API_KEY');

    foreach (readmeLines() as $number => $line) {
        if (str_contains($line, 'FLEETBASE_API_KEY') && str_contains($line, '-u ')) {
            throw new RuntimeException('README line ' . ($number + 1) . ' documents basic auth; the API only accepts a bearer token');
        }
    }
});
