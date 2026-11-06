<?php

use Fleetbase\Pallet\Http\Controllers\AuditController;
use Fleetbase\Pallet\Models\Audit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * AuditController builds its own query instead of going through the resource
 * controller, so it also has to apply the resource envelope itself. It did not,
 * and the collection fell back to Laravel's default `data` key — which the Ember
 * store cannot match, so the audits screen rendered nothing at all.
 */
function auditIndexPayload(string $uri = 'pallet/int/v1/audits'): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    Audit::create([
        'company_uuid' => $company,
        'event_type'   => 'stock_adjustment',
        'action'       => 'Stock Adjusted',
        'type'         => 'manual',
    ]);

    // The envelope is applied to internal requests only, which is decided from
    // the bound route — so the request has to carry one to exercise the real path.
    $request = Request::create('/' . $uri, 'GET');
    $route   = new Route(['GET'], $uri, ['namespace' => '\\Fleetbase\\Pallet']);
    $request->setRouteResolver(fn () => $route);

    $response = (new AuditController())->index($request);

    return json_decode(json_encode($response->response()->getData(true)), true);
}

test('the audit index is served under the envelope key the store expects', function () {
    $payload = auditIndexPayload();

    expect($payload)->toHaveKey('audits')
        ->and($payload)->not->toHaveKey('data')
        ->and($payload['audits'])->toBeArray()
        ->and($payload['audits'])->toHaveCount(1);
});

test('the audit envelope key matches the model payload key', function () {
    expect((new Audit())->getPluralName())->toBe('audits');
});
