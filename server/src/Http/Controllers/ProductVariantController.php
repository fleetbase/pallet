<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\Internal\v1\ProductVariant as ProductVariantResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductVariantController extends PalletResourceController
{
    public $resource = 'product-variant';

    public function index(string $product)
    {
        $product = Product::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $product)->orWhere('public_id', $product))
            ->firstOrFail();

        return ProductVariantResource::collection($product->variants()->orderBy('created_at')->get());
    }

    public function store(Request $request, string $product)
    {
        $product = Product::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $product)->orWhere('public_id', $product))
            ->firstOrFail();

        $input = $request->input('product_variant', $request->all());
        if ($this->skuExists(data_get($input, 'sku'))) {
            return response()->error('The SKU is already in use by another Pallet product or variant.', 422);
        }

        $variant = ProductVariant::create(array_merge($input, [
            'uuid'            => Str::uuid(),
            'company_uuid'    => session('company'),
            'created_by_uuid' => session('user'),
            'product_uuid'    => $product->uuid,
            'status'          => data_get($input, 'status', 'active'),
        ]));

        return new ProductVariantResource($variant->load('product'));
    }

    public function update(Request $request, string $product, string $variant)
    {
        $product = Product::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $product)->orWhere('public_id', $product))
            ->firstOrFail();

        $variant = ProductVariant::where('product_uuid', $product->uuid)
            ->where(fn ($query) => $query->where('uuid', $variant)->orWhere('public_id', $variant))
            ->firstOrFail();

        $input = $request->input('product_variant', $request->all());
        if ($this->skuExists(data_get($input, 'sku'), $variant->uuid)) {
            return response()->error('The SKU is already in use by another Pallet product or variant.', 422);
        }

        $variant->fill($input);
        $variant->save();

        return new ProductVariantResource($variant->load('product'));
    }

    public function destroy(string $product, string $variant)
    {
        $product = Product::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $product)->orWhere('public_id', $product))
            ->firstOrFail();

        $variant = ProductVariant::where('product_uuid', $product->uuid)
            ->where(fn ($query) => $query->where('uuid', $variant)->orWhere('public_id', $variant))
            ->firstOrFail();

        $variant->delete();

        return response()->json(['status' => 'ok', 'message' => 'Product variant deleted.']);
    }

    protected function skuExists(?string $sku, ?string $exceptVariantUuid = null): bool
    {
        if (!$sku) {
            return false;
        }

        $variantQuery = ProductVariant::where('company_uuid', session('company'))->where('sku', $sku);
        if ($exceptVariantUuid) {
            $variantQuery->where('uuid', '!=', $exceptVariantUuid);
        }

        return Product::where('company_uuid', session('company'))->where('sku', $sku)->exists() || $variantQuery->exists();
    }
}
