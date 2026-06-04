<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Pallet\Http\Resources\Product as ProductResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
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

            $product = new Product(array_merge($data, [
                'company_uuid'    => session('company'),
                'created_by_uuid' => session('user'),
                'status'          => data_get($data, 'status', 'active'),
            ]));
            $product->save();

            if (Http::isInternalRequest($request)) {
                ProductResource::wrap($this->resourceSingularlName);
            }

            return new ProductResource($product->load(['supplier', 'category', 'variants']));
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

            $product->fill($data);
            $product->save();

            if (Http::isInternalRequest($request)) {
                ProductResource::wrap($this->resourceSingularlName);
            }

            return new ProductResource($product->load(['supplier', 'category', 'variants']));
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
}
