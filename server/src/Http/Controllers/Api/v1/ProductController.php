<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Models\Category;
use Fleetbase\Pallet\Http\Requests\CreateProductRequest;
use Fleetbase\Pallet\Http\Requests\UpdateProductRequest;
use Fleetbase\Pallet\Http\Resources\v1\DeletedResource;
use Fleetbase\Pallet\Http\Resources\v1\Product as ProductResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Consumable product API.
 *
 * Records are addressed by public id. Relations are accepted and returned as public
 * ids too, so a consumer never handles a uuid.
 */
class ProductController extends Controller
{
    /**
     * Fields a consumer may set directly. `supplier` and `category` are resolved from
     * public ids separately, and everything else on the model — company, creator,
     * storefront linkage — is assigned by Pallet rather than the caller.
     */
    protected array $writable = [
        'name',
        'description',
        'sku',
        'barcode',
        'internal_id',
        'status',
        'currency',
        'unit_cost',
        'unit_price',
        'sale_price',
        'declared_value',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimensions_unit',
        'has_variants',
        'is_serialized',
        'is_lot_tracked',
        'is_kit',
        'is_perishable',
        'requires_quality_check',
        'reorder_point',
        'reorder_quantity',
        'shelf_life_days',
        'meta',
    ];

    public function create(CreateProductRequest $request)
    {
        $input                 = $request->only($this->writable);
        $input['company_uuid'] = session('company');

        $relations = $this->resolveRelations($request);

        if ($relations instanceof \Illuminate\Http\JsonResponse) {
            return $relations;
        }

        $product = Product::create(array_merge($input, $relations));

        return new ProductResource($product->fresh(['supplier', 'category']));
    }

    public function update($id, UpdateProductRequest $request)
    {
        try {
            $product = Product::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $relations = $this->resolveRelations($request);

        if ($relations instanceof \Illuminate\Http\JsonResponse) {
            return $relations;
        }

        $product->update(array_merge($request->only($this->writable), $relations));

        return new ProductResource($product->fresh(['supplier', 'category']));
    }

    public function query(Request $request)
    {
        return ProductResource::collection(Product::queryWithRequest($request));
    }

    public function find($id, Request $request)
    {
        try {
            $product = Product::findRecordOrFail($id, ['supplier', 'category']);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new ProductResource($product);
    }

    public function delete($id, Request $request)
    {
        try {
            $product = Product::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $product->delete();

        return new DeletedResource($product);
    }

    /**
     * Translate the public ids a consumer sends for `supplier` and `category` into the
     * uuids the columns hold. Both lookups are company-scoped, so a public id belonging
     * to another company reads as absent rather than resolving.
     *
     * Returns a JsonResponse instead of an array when a supplied id does not resolve —
     * silently dropping it would attach the product to nothing and report success.
     */
    protected function resolveRelations(Request $request): array|\Illuminate\Http\JsonResponse
    {
        $resolved = [];

        if ($request->filled('supplier')) {
            $supplier = Supplier::where('company_uuid', session('company'))
                ->where('public_id', $request->input('supplier'))
                ->first();

            if (!$supplier) {
                return response()->json(['error' => 'Supplier not found.'], 404);
            }

            $resolved['supplier_uuid'] = $supplier->uuid;
        }

        if ($request->filled('category')) {
            $category = Category::where('company_uuid', session('company'))
                ->where('public_id', $request->input('category'))
                ->first();

            if (!$category) {
                return response()->json(['error' => 'Category not found.'], 404);
            }

            $resolved['category_uuid'] = $category->uuid;
        }

        return $resolved;
    }

    protected function notFound(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['error' => 'Product resource not found.'], 404);
    }
}
