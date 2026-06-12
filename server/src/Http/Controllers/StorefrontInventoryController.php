<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Resources\InventoryReservation as InventoryReservationResource;
use Fleetbase\Pallet\Http\Resources\Product as ProductResource;
use Fleetbase\Pallet\Http\Resources\ProductVariant as ProductVariantResource;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorefrontInventoryController extends Controller
{
    public function resolve(Request $request)
    {
        $product = $this->resolveProduct($request);
        $variant = $product ? $this->resolveVariant($request, $product) : null;

        if (!$product) {
            return response()->json(['product' => null, 'variant' => null]);
        }

        return response()->json([
            'product' => new ProductResource($product->load(['supplier', 'category', 'variants'])),
            'variant' => $variant ? new ProductVariantResource($variant) : null,
        ]);
    }

    public function link(Request $request)
    {
        $product = $this->findProduct($request->input('pallet_product_uuid') ?? $request->input('product_uuid') ?? $request->input('product'));

        $product->storefront_product_uuid = $request->input('storefront_product_uuid');
        $product->save();

        if ($request->filled('variant_uuid') || $request->filled('pallet_variant_uuid')) {
            $variant = $this->findVariant($request->input('pallet_variant_uuid') ?? $request->input('variant_uuid'), $product);
            $variant->storefront_variant_uuid = $request->input('storefront_variant_uuid');
            $variant->save();
        }

        return new ProductResource($product->fresh(['supplier', 'category', 'variants']));
    }

    public function unlink(Request $request)
    {
        $product = $this->findProduct($request->input('pallet_product_uuid') ?? $request->input('product_uuid') ?? $request->input('product'));

        $product->storefront_product_uuid = null;
        $product->save();

        $product->variants()->update(['storefront_variant_uuid' => null]);

        return new ProductResource($product->fresh(['supplier', 'category', 'variants']));
    }

    public function availability(Request $request)
    {
        $product = $this->resolveProduct($request);

        if (!$product) {
            return response()->json([
                'available' => false,
                'available_quantity' => 0,
                'reserved_quantity' => 0,
                'total_quantity' => 0,
                'message' => 'No linked Pallet product was found.',
            ], 404);
        }

        $variant = $this->resolveVariant($request, $product);
        $totals = $this->inventoryQuery($product, $variant, $request)
            ->selectRaw('SUM(quantity) as total_quantity, SUM(available_quantity) as available_quantity, SUM(reserved_quantity) as reserved_quantity')
            ->first();

        $availableQuantity = (int) ($totals->available_quantity ?? 0);
        $requestedQuantity = (int) $request->input('quantity', 1);

        return response()->json([
            'available' => $availableQuantity >= $requestedQuantity,
            'available_quantity' => $availableQuantity,
            'reserved_quantity' => (int) ($totals->reserved_quantity ?? 0),
            'total_quantity' => (int) ($totals->total_quantity ?? 0),
            'requested_quantity' => $requestedQuantity,
            'product_uuid' => $product->uuid,
            'variant_uuid' => $variant?->uuid,
        ]);
    }

    public function reserve(Request $request)
    {
        $quantity = max(1, (int) $request->input('quantity', 1));
        $product = $this->resolveProduct($request);

        if (!$product) {
            return response()->error('No linked Pallet product was found.', 404);
        }

        $variant = $this->resolveVariant($request, $product);

        return DB::transaction(function () use ($request, $product, $variant, $quantity) {
            $inventory = $this->inventoryQuery($product, $variant, $request)
                ->where('available_quantity', '>=', $quantity)
                ->orderByRaw('CASE WHEN expiry_date_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expiry_date_at')
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                $available = (int) $this->inventoryQuery($product, $variant, $request)->sum('available_quantity');

                return response()->json([
                    'error' => 'Insufficient inventory available for this Storefront product.',
                    'available_quantity' => $available,
                    'requested_quantity' => $quantity,
                    'product_uuid' => $product->uuid,
                    'variant_uuid' => $variant?->uuid,
                ], 422);
            }

            $inventory->reserve($quantity);

            $reservation = InventoryReservation::create([
                'company_uuid' => session('company'),
                'product_uuid' => $product->uuid,
                'variant_uuid' => $variant?->uuid,
                'inventory_uuid' => $inventory->uuid,
                'warehouse_uuid' => $inventory->warehouse_uuid,
                'order_uuid' => $request->input('order_uuid') ?? $request->input('storefront_order_uuid'),
                'quantity' => $quantity,
                'expires_at' => $request->input('expires_at') ?? now()->addMinutes(30),
                'type' => 'hard',
                'status' => 'active',
                'meta' => [
                    'source' => 'storefront',
                    'storefront_product_uuid' => $request->input('storefront_product_uuid'),
                    'storefront_variant_uuid' => $request->input('storefront_variant_uuid'),
                    'storefront_store_uuid' => $request->input('storefront_store_uuid'),
                ],
            ]);

            return new InventoryReservationResource($reservation->fresh(['product', 'variant', 'inventory', 'warehouse']));
        });
    }

    public function release(string $id)
    {
        $reservation = $this->findReservation($id);
        $reservation->release();

        return new InventoryReservationResource($reservation->fresh(['product', 'variant', 'inventory', 'warehouse']));
    }

    public function commit(string $id)
    {
        $reservation = $this->findReservation($id);
        $reservation->fulfill();

        return new InventoryReservationResource($reservation->fresh(['product', 'variant', 'inventory', 'warehouse']));
    }

    protected function resolveProduct(Request $request): ?Product
    {
        $id = $request->input('pallet_product_uuid') ?? $request->input('product_uuid') ?? $request->input('product');

        if ($id) {
            return Product::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
                ->first();
        }

        if ($request->filled('storefront_product_uuid')) {
            return Product::where('company_uuid', session('company'))
                ->where('storefront_product_uuid', $request->input('storefront_product_uuid'))
                ->first();
        }

        if ($request->filled('sku')) {
            return Product::where('company_uuid', session('company'))->where('sku', $request->input('sku'))->first();
        }

        if ($request->filled('barcode')) {
            return Product::where('company_uuid', session('company'))->where('barcode', $request->input('barcode'))->first();
        }

        return null;
    }

    protected function resolveVariant(Request $request, Product $product): ?ProductVariant
    {
        $id = $request->input('pallet_variant_uuid') ?? $request->input('variant_uuid') ?? $request->input('variant');

        if ($id) {
            return $this->findVariant($id, $product);
        }

        if ($request->filled('storefront_variant_uuid')) {
            return $product->variants()
                ->where('storefront_variant_uuid', $request->input('storefront_variant_uuid'))
                ->first();
        }

        if ($request->filled('variant_sku')) {
            return $product->variants()->where('sku', $request->input('variant_sku'))->first();
        }

        return null;
    }

    protected function inventoryQuery(Product $product, ?ProductVariant $variant, Request $request)
    {
        return Inventory::where('company_uuid', session('company'))
            ->where('product_uuid', $product->uuid)
            ->when($variant, fn ($query) => $query->where('variant_uuid', $variant->uuid))
            ->when(!$variant && !$product->has_variants, fn ($query) => $query->whereNull('variant_uuid'))
            ->when($request->filled('warehouse_uuid'), fn ($query) => $query->where('warehouse_uuid', $request->input('warehouse_uuid')))
            ->where('status', 'active');
    }

    protected function findProduct(string $id): Product
    {
        return Product::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
            ->firstOrFail();
    }

    protected function findVariant(string $id, Product $product): ProductVariant
    {
        return $product->variants()
            ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
            ->firstOrFail();
    }

    protected function findReservation(string $id): InventoryReservation
    {
        return InventoryReservation::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
            ->firstOrFail();
    }
}
