<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Requests\CreateProductVariantRequest;
use Fleetbase\Pallet\Http\Requests\UpdateProductVariantRequest;
use Fleetbase\Pallet\Http\Resources\v1\DeletedResource;
use Fleetbase\Pallet\Http\Resources\v1\ProductVariant as ProductVariantResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    protected array $writable = [
        'name',
        'sku',
        'barcode',
        'option_values',
        'currency',
        'unit_cost',
        'unit_price',
        'sale_price',
        'declared_value',
        'weight',
        'weight_unit',
        'status',
        'meta',
    ];

    public function create(CreateProductVariantRequest $request)
    {
        $product = $this->resolveProduct($request);

        if ($product instanceof JsonResponse) {
            return $product;
        }

        $variant = ProductVariant::create(array_merge($request->only($this->writable), [
            'company_uuid' => session('company'),
            'product_uuid' => $product->uuid,
        ]));

        return new ProductVariantResource($variant->fresh(['product']));
    }

    public function update($id, UpdateProductVariantRequest $request)
    {
        try {
            $variant = ProductVariant::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $variant->update($request->only($this->writable));

        return new ProductVariantResource($variant->fresh(['product']));
    }

    public function query(Request $request)
    {
        return ProductVariantResource::collection(
            ProductVariant::queryWithRequest($request, function ($query) {
                $query->with('product');
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $variant = ProductVariant::findRecordOrFail($id, ['product']);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new ProductVariantResource($variant);
    }

    public function delete($id, Request $request)
    {
        try {
            $variant = ProductVariant::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $variant->delete();

        return new DeletedResource($variant);
    }

    protected function resolveProduct(Request $request): Product|JsonResponse
    {
        $product = Product::where('company_uuid', session('company'))
            ->where('public_id', $request->input('product'))
            ->first();

        return $product ?: response()->json(['error' => 'Product not found.'], 404);
    }

    protected function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Product variant resource not found.'], 404);
    }
}
