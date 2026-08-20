<?php

use Fleetbase\Pallet\Http\Controllers\Api\v1\ProductController;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

/**
 * Contract for the consumable API, proven on one resource before the shape is
 * repeated across the rest of the domain.
 *
 * What has to hold: records are addressed and returned by public id, internal uuids
 * never appear, listings are scoped to the calling company, another company's record
 * is indistinguishable from one that does not exist, and a relation that fails to
 * resolve is an error rather than a silent null.
 */
function publicApiRequest(string $uri, string $method = 'GET', array $payload = []): Request
{
    $request = Request::create('/pallet/v1/' . $uri, $method, $payload);
    $request->setLaravelSession(app('session.store'));

    $route = new Route([$method], 'pallet/v1/' . $uri, [
        'namespace'  => '\\Fleetbase\\Pallet',
        'controller' => ProductController::class . '@query',
    ]);
    $route->controller = app(ProductController::class);
    $request->setRouteResolver(fn () => $route);

    return $request;
}

function asCompany(string $company): void
{
    session(['company' => $company, 'api_credential' => 'test-credential']);
}

function makePublicProduct(string $company, string $name): Product
{
    asCompany($company);

    return Product::create([
        'company_uuid' => $company,
        'name'         => $name,
        'sku'          => 'PUB-' . uniqid(),
    ]);
}

function resourceArray($resource, Request $request): array
{
    return json_decode(json_encode($resource->toArray($request)), true);
}

test('a product is addressed and returned by its public id', function () {
    $company = (string) Str::uuid();
    $product = makePublicProduct($company, 'Public Product');

    $request  = publicApiRequest('products/' . $product->public_id);
    $resource = (new ProductController())->find($product->public_id, $request);
    $body     = resourceArray($resource, $request);

    expect($body['id'])->toBe($product->public_id)
        ->and($body['id'])->toStartWith('product_')
        ->and($body['object'])->toBe('product')
        ->and($body['name'])->toBe('Public Product');
});

test('the consumable representation exposes no internal identifiers', function () {
    $company = (string) Str::uuid();
    $product = makePublicProduct($company, 'Opaque Product');

    $request = publicApiRequest('products/' . $product->public_id);
    $body    = resourceArray((new ProductController())->find($product->public_id, $request), $request);

    foreach (['uuid', 'public_id', 'company_uuid', 'created_by_uuid', 'category_uuid', 'supplier_uuid', 'photo_uuid', 'storefront_product_uuid'] as $leaked) {
        expect(array_key_exists($leaked, $body))->toBeFalse($leaked . ' must not appear in the consumable representation');
    }

    expect(json_encode($body))->not->toContain($product->uuid)
        ->and(json_encode($body))->not->toContain($company);
});

test('a listing returns only the calling company products', function () {
    $companyA = (string) Str::uuid();
    $companyB = (string) Str::uuid();

    $ours = makePublicProduct($companyA, 'Ours');
    makePublicProduct($companyB, 'Theirs');

    asCompany($companyA);
    $request = publicApiRequest('products');
    $results = Product::queryWithRequest($request);

    expect($results)->toHaveCount(1)
        ->and($results->first()->public_id)->toBe($ours->public_id);
});

test('another company product is not found rather than forbidden', function () {
    $companyA = (string) Str::uuid();
    $companyB = (string) Str::uuid();

    $theirs = makePublicProduct($companyB, 'Theirs');

    asCompany($companyA);
    $response = (new ProductController())->find($theirs->public_id, publicApiRequest('products/' . $theirs->public_id));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true)['error'])->toBe('Product resource not found.');
});

test('a product is created against the calling company', function () {
    $company = (string) Str::uuid();
    asCompany($company);

    $request  = publicApiRequest('products', 'POST', ['name' => 'Created Product', 'sku' => 'CRT-1', 'unit_price' => 12.5]);
    $resource = (new ProductController())->create(
        Fleetbase\Pallet\Http\Requests\CreateProductRequest::createFrom($request)
    );
    $body = resourceArray($resource, $request);

    $created = Product::where('public_id', $body['id'])->first();

    expect($body['name'])->toBe('Created Product')
        ->and($created)->not->toBeNull()
        ->and($created->company_uuid)->toBe($company);
});

test('a consumer cannot assign a product to another company', function () {
    $company = (string) Str::uuid();
    $other   = (string) Str::uuid();
    asCompany($company);

    $request  = publicApiRequest('products', 'POST', ['name' => 'Smuggled', 'company_uuid' => $other]);
    $resource = (new ProductController())->create(
        Fleetbase\Pallet\Http\Requests\CreateProductRequest::createFrom($request)
    );
    $body = resourceArray($resource, $request);

    expect(Product::where('public_id', $body['id'])->first()->company_uuid)->toBe($company);
});

test('an update applies only the fields sent', function () {
    $company = (string) Str::uuid();
    $product = makePublicProduct($company, 'Before');
    $sku     = $product->sku;

    $request  = publicApiRequest('products/' . $product->public_id, 'PUT', ['name' => 'After']);
    $resource = (new ProductController())->update(
        $product->public_id,
        Fleetbase\Pallet\Http\Requests\UpdateProductRequest::createFrom($request)
    );
    $body = resourceArray($resource, $request);

    expect($body['name'])->toBe('After')
        ->and($body['sku'])->toBe($sku);
});

test('a delete confirms with the public id and removes the record', function () {
    $company = (string) Str::uuid();
    $product = makePublicProduct($company, 'Doomed');

    $request  = publicApiRequest('products/' . $product->public_id, 'DELETE');
    $resource = (new ProductController())->delete($product->public_id, $request);
    $body     = resourceArray($resource, $request);

    expect($body['id'])->toBe($product->public_id)
        ->and($body['deleted'])->toBeTrue()
        ->and(Product::where('public_id', $product->public_id)->first())->toBeNull();
});

test('a supplier is attached by public id', function () {
    $company = (string) Str::uuid();
    asCompany($company);

    $supplier = Supplier::create(['company_uuid' => $company, 'name' => 'Acme Supply']);

    $request  = publicApiRequest('products', 'POST', ['name' => 'Sourced', 'supplier' => $supplier->public_id]);
    $resource = (new ProductController())->create(
        Fleetbase\Pallet\Http\Requests\CreateProductRequest::createFrom($request)
    );
    $body = resourceArray($resource, $request);

    expect($body['supplier'])->toBe($supplier->public_id)
        ->and(Product::where('public_id', $body['id'])->first()->supplier_uuid)->toBe($supplier->uuid);
});

test('a supplier belonging to another company does not resolve', function () {
    $companyA = (string) Str::uuid();
    $companyB = (string) Str::uuid();

    asCompany($companyB);
    $theirSupplier = Supplier::create(['company_uuid' => $companyB, 'name' => 'Their Supply']);

    asCompany($companyA);
    $request  = publicApiRequest('products', 'POST', ['name' => 'Cross Tenant', 'supplier' => $theirSupplier->public_id]);
    $response = (new ProductController())->create(
        Fleetbase\Pallet\Http\Requests\CreateProductRequest::createFrom($request)
    );

    expect($response->getStatusCode())->toBe(404)
        ->and(Product::where('name', 'Cross Tenant')->first())->toBeNull();
});

test('the consumable routes are registered behind the api credential middleware', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'pallet/v1/'));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect(in_array('fleetbase.api', $route->gatherMiddleware(), true))->toBeTrue($route->uri() . ' must require an API credential');
    }
});
