<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Models\Category;
use Fleetbase\Pallet\Http\Resources\Product as ProductResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Fleetbase\Pallet\Models\Supplier;
use Fleetbase\Support\Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProductController extends PalletResourceController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'product';

    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);
            $data = $request->input('product', []);

            if ($this->skuExists(data_get($data, 'sku'))) {
                return response()->error('The SKU is already in use by another Pallet product or variant.', 422);
            }

            $normalized = $this->normalizeProductReferences($data);
            if (!is_array($normalized)) {
                return $normalized;
            }

            $product = new Product(array_merge($normalized, [
                'company_uuid'    => session('company'),
                'created_by_uuid' => session('user'),
                'status'          => data_get($normalized, 'status', 'active'),
            ]));
            $product->save();

            if (Http::isInternalRequest($request)) {
                ProductResource::wrap($this->resourceSingularlName);
            }

            return new ProductResource($product->load(['supplier', 'category', 'variants', 'files']));
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        }
    }

    public function updateRecord(Request $request, string $id)
    {
        try {
            $this->validateRequest($request);
            $data    = $request->input('product', []);
            $product = Product::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
                ->firstOrFail();

            if ($this->skuExists(data_get($data, 'sku'), $product->uuid)) {
                return response()->error('The SKU is already in use by another Pallet product or variant.', 422);
            }

            $normalized = $this->normalizeProductReferences($data);
            if (!is_array($normalized)) {
                return $normalized;
            }

            $product->fill($normalized);
            $product->save();

            if (Http::isInternalRequest($request)) {
                ProductResource::wrap($this->resourceSingularlName);
            }

            return new ProductResource($product->load(['supplier', 'category', 'variants', 'files']));
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        }
    }

    protected function skuExists(?string $sku, ?string $exceptProductUuid = null): bool
    {
        if (!$sku) {
            return false;
        }

        $productQuery = Product::where('company_uuid', session('company'))->where('sku', $sku);
        if ($exceptProductUuid) {
            $productQuery->where('uuid', '!=', $exceptProductUuid);
        }

        return $productQuery->exists() || ProductVariant::where('company_uuid', session('company'))->where('sku', $sku)->exists();
    }

    protected function normalizeProductReferences(array $data)
    {
        $supplierId = data_get($data, 'supplier_uuid');
        $categoryId = data_get($data, 'category_uuid');

        if ($supplierId) {
            $supplier = Supplier::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $supplierId)->orWhere('public_id', $supplierId))
                ->first();

            if (!$supplier) {
                return response()->error('Selected supplier could not be found.', 422);
            }

            $data['supplier_uuid'] = $supplier->uuid;
        }

        if ($categoryId) {
            $category = Category::where(fn ($query) => $query->where('uuid', $categoryId)->orWhere('public_id', $categoryId))
                ->where(function ($query) {
                    $query->where('company_uuid', session('company'))
                        ->orWhere('owner_uuid', session('company'))
                        ->orWhere('core_category', true);
                })
                ->first();

            if (!$category) {
                return response()->error('Selected product category could not be found.', 422);
            }

            $data['category_uuid'] = $category->uuid;
        }

        return $data;
    }
}
