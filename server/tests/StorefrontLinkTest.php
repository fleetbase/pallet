<?php

use Fleetbase\Pallet\Http\Controllers\StorefrontInventoryController;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Illuminate\Http\Request;

function makeLinkFixture(): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $product = Product::create(['company_uuid' => $company, 'name' => 'Linked Product', 'sku' => 'LNK-' . uniqid()]);
    $variant = ProductVariant::create([
        'company_uuid' => $company,
        'product_uuid' => $product->uuid,
        'name'         => 'Variant A',
        'sku'          => 'LNKV-' . uniqid(),
    ]);

    return [$company, $product, $variant];
}

test('linking binds a storefront product and unlink clears product and variants', function () {
    [, $product, $variant] = makeLinkFixture();
    $storefrontUuid        = (string) Illuminate\Support\Str::uuid();
    $storefrontVariantUuid = (string) Illuminate\Support\Str::uuid();

    $controller = new StorefrontInventoryController();

    $controller->link(Request::create('/', 'POST', [
        'pallet_product_uuid'     => $product->uuid,
        'storefront_product_uuid' => $storefrontUuid,
        'variants'                => [
            ['pallet_variant_uuid' => $variant->uuid, 'storefront_variant_uuid' => $storefrontVariantUuid],
        ],
    ]));

    expect($product->fresh()->storefront_product_uuid)->toBe($storefrontUuid)
        ->and($variant->fresh()->storefront_variant_uuid)->toBe($storefrontVariantUuid);

    $controller->unlink(Request::create('/', 'POST', ['pallet_product_uuid' => $product->uuid]));

    expect($product->fresh()->storefront_product_uuid)->toBeNull()
        ->and($variant->fresh()->storefront_variant_uuid)->toBeNull();
});

test('a storefront product cannot be linked to two pallet products', function () {
    [$company, $product] = makeLinkFixture();
    $storefrontUuid      = (string) Illuminate\Support\Str::uuid();

    $controller = new StorefrontInventoryController();
    $controller->link(Request::create('/', 'POST', [
        'pallet_product_uuid'     => $product->uuid,
        'storefront_product_uuid' => $storefrontUuid,
    ]));

    $second = Product::create(['company_uuid' => $company, 'name' => 'Second Product', 'sku' => 'LNK2-' . uniqid()]);

    $response = $controller->link(Request::create('/', 'POST', [
        'pallet_product_uuid'     => $second->uuid,
        'storefront_product_uuid' => $storefrontUuid,
    ]));

    expect($response->getStatusCode())->toBeGreaterThanOrEqual(400)
        ->and($second->fresh()->storefront_product_uuid)->toBeNull();
});

test('the database rejects duplicate storefront links as a race backstop', function () {
    [$company]      = makeLinkFixture();
    $storefrontUuid = (string) Illuminate\Support\Str::uuid();

    Product::create(['company_uuid' => $company, 'name' => 'P1', 'sku' => 'B1-' . uniqid(), 'storefront_product_uuid' => $storefrontUuid]);

    expect(fn () => Product::create(['company_uuid' => $company, 'name' => 'P2', 'sku' => 'B2-' . uniqid(), 'storefront_product_uuid' => $storefrontUuid]))
        ->toThrow(Illuminate\Database\QueryException::class);
});
