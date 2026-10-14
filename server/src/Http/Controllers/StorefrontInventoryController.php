<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Resources\Internal\v1\InventoryReservation as InventoryReservationResource;
use Fleetbase\Pallet\Http\Resources\Internal\v1\Product as ProductResource;
use Fleetbase\Pallet\Http\Resources\Internal\v1\ProductVariant as ProductVariantResource;
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
        $product = $this->resolveProduct($request, true);
        $variant = $product ? $this->resolveVariant($request, $product, true) : null;

        if (!$product) {
            return response()->json(['product' => null, 'variant' => null]);
        }

        if ($this->hasVariantSelector($request) && !$variant) {
            return response()->json([
                'product'           => new ProductResource($product->load(['supplier', 'category', 'variants'])),
                'variant'           => null,
                'inventory_summary' => null,
                'message'           => 'No linked Pallet variant was found.',
            ], 404);
        }

        return response()->json([
            'product'           => new ProductResource($product->load(['supplier', 'category', 'variants'])),
            'variant'           => $variant ? new ProductVariantResource($variant) : null,
            'inventory_summary' => $this->inventorySummary($product, $variant, $request),
        ]);
    }

    public function link(Request $request)
    {
        $productId = $request->input('pallet_product_uuid') ?? $request->input('product_uuid') ?? $request->input('product');

        if (!$productId) {
            return response()->error('A Pallet product UUID is required to link inventory.', 422);
        }

        if (!$request->filled('storefront_product_uuid')) {
            return response()->error('A Storefront product UUID is required to link inventory.', 422);
        }

        try {
            $product = DB::transaction(function () use ($request, $productId) {
                $product             = $this->findProduct($productId);
                $storefrontProductId = $request->input('storefront_product_uuid');

                $this->assertStorefrontProductLinkAvailable($storefrontProductId, $product);

                $product->storefront_product_uuid = $storefrontProductId;

                if ($request->filled('variants')) {
                    foreach (collect($request->input('variants')) as $variantLink) {
                        $this->linkStorefrontVariant($product, $variantLink);
                    }
                } elseif ($request->filled('variant_uuid') || $request->filled('pallet_variant_uuid')) {
                    if (!$request->filled('storefront_variant_uuid')) {
                        throw new \RuntimeException('A Storefront variant UUID is required to link a Pallet variant.', 422);
                    }

                    $this->linkStorefrontVariant($product, [
                        'pallet_variant_uuid'     => $request->input('pallet_variant_uuid') ?? $request->input('variant_uuid'),
                        'storefront_variant_uuid' => $request->input('storefront_variant_uuid'),
                    ]);
                }

                $product->save();

                return $product;
            });
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), $this->statusCodeFromException($e));
        }

        return new ProductResource($product->fresh(['supplier', 'category', 'variants']));
    }

    public function unlink(Request $request)
    {
        $productId = $request->input('pallet_product_uuid') ?? $request->input('product_uuid') ?? $request->input('product');

        if (!$productId && !$request->filled('storefront_product_uuid')) {
            return response()->error('A Pallet product UUID or Storefront product UUID is required to unlink inventory.', 422);
        }

        $product = $this->findProduct($productId, $request->input('storefront_product_uuid'));

        DB::transaction(function () use ($product) {
            $product->storefront_product_uuid = null;
            $product->save();

            $product->variants()->update(['storefront_variant_uuid' => null]);
        });

        return new ProductResource($product->fresh(['supplier', 'category', 'variants']));
    }

    public function availability(Request $request)
    {
        $product = $this->resolveProduct($request);

        if (!$product) {
            return response()->json([
                'available'               => false,
                'available_quantity'      => 0,
                'reserved_quantity'       => 0,
                'total_quantity'          => 0,
                'requested_quantity'      => max(1, (int) $request->input('quantity', 1)),
                'shortage_quantity'       => max(1, (int) $request->input('quantity', 1)),
                'out_of_stock'            => true,
                'low_stock'               => true,
                'product_uuid'            => null,
                'variant_uuid'            => null,
                'storefront_product_uuid' => $request->input('storefront_product_uuid'),
                'storefront_variant_uuid' => $request->input('storefront_variant_uuid'),
                'inventory_summary'       => null,
                'message'                 => 'No linked Pallet product was found.',
            ], 404);
        }

        return response()->json($this->availabilityPayload($request, $product));
    }

    public function availabilityBatch(Request $request)
    {
        $items = collect($request->input('items', []))->values();

        if ($items->isEmpty()) {
            return response()->error('At least one Storefront inventory availability item is required.', 422);
        }

        $results = $items->map(function ($item, $index) use ($request) {
            if (is_object($item)) {
                $item = (array) $item;
            }

            if (!is_array($item)) {
                return [
                    'available'  => false,
                    'line_index' => $index,
                    'error'      => "Availability batch item {$index} must be an object.",
                ];
            }

            $lineRequest = $request->duplicate(null, array_merge($request->request->all(), $item));
            $product     = $this->resolveProduct($lineRequest);

            if (!$product) {
                return [
                    'available'               => false,
                    'line_index'              => $index,
                    'available_quantity'      => 0,
                    'reserved_quantity'       => 0,
                    'total_quantity'          => 0,
                    'requested_quantity'      => max(1, (int) $lineRequest->input('quantity', 1)),
                    'shortage_quantity'       => max(1, (int) $lineRequest->input('quantity', 1)),
                    'out_of_stock'            => true,
                    'low_stock'               => true,
                    'product_uuid'            => null,
                    'variant_uuid'            => null,
                    'storefront_product_uuid' => $lineRequest->input('storefront_product_uuid'),
                    'storefront_variant_uuid' => $lineRequest->input('storefront_variant_uuid'),
                    'inventory_summary'       => null,
                    'error'                   => 'No linked Pallet product was found.',
                ];
            }

            return array_merge(['line_index' => $index], $this->availabilityPayload($lineRequest, $product));
        });

        return response()->json([
            'available' => $results->every(fn ($item) => (bool) data_get($item, 'available')),
            'items'     => $results,
        ]);
    }

    public function reserve(Request $request)
    {
        try {
            $reservation = DB::transaction(fn () => $this->createStorefrontReservation($request));

            return new InventoryReservationResource($reservation->fresh(['product', 'variant', 'inventory', 'warehouse']));
        } catch (\RuntimeException $e) {
            return $this->reservationErrorResponse($e, $request);
        }
    }

    public function reserveBatch(Request $request)
    {
        $items = collect($request->input('items', []))->values();

        if ($items->isEmpty()) {
            return response()->error('At least one Storefront inventory reservation item is required.', 422);
        }

        try {
            $reservations = DB::transaction(function () use ($request, $items) {
                return $items->map(function ($item, $index) use ($request) {
                    if (is_object($item)) {
                        $item = (array) $item;
                    }

                    if (!is_array($item)) {
                        throw new \RuntimeException("Reservation batch item {$index} must be an object.", 422);
                    }

                    $lineRequest = $request->duplicate(null, array_merge($request->request->all(), $item));

                    return $this->createStorefrontReservation($lineRequest);
                });
            });

            return InventoryReservationResource::collection($reservations->map->fresh(['product', 'variant', 'inventory', 'warehouse']));
        } catch (\RuntimeException $e) {
            return $this->reservationErrorResponse($e, $request);
        }
    }

    public function releaseBatch(Request $request)
    {
        return $this->transitionReservationBatch($request, 'release');
    }

    public function commitBatch(Request $request)
    {
        return $this->transitionReservationBatch($request, 'fulfill');
    }

    public function contextReservations(Request $request)
    {
        try {
            $reservations = $this->storefrontReservationContextQuery($request)->get();

            return InventoryReservationResource::collection($reservations->map->fresh(['product', 'variant', 'inventory', 'warehouse']));
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), $this->statusCodeFromException($e));
        }
    }

    public function releaseContext(Request $request)
    {
        return $this->transitionReservationContext($request, 'release');
    }

    public function commitContext(Request $request)
    {
        return $this->transitionReservationContext($request, 'fulfill');
    }

    protected function inventorySummary(Product $product, ?ProductVariant $variant, Request $request): array
    {
        $totals = $this->inventoryQuery($product, $variant, $request)
            ->selectRaw('SUM(quantity) as total_quantity, SUM(available_quantity) as available_quantity, SUM(reserved_quantity) as reserved_quantity')
            ->first();

        $availableQuantity = (int) ($totals->available_quantity ?? 0);
        $reservedQuantity  = (int) ($totals->reserved_quantity ?? 0);
        $totalQuantity     = (int) ($totals->total_quantity ?? 0);

        return [
            'product_uuid'             => $product->uuid,
            'variant_uuid'             => $variant?->uuid,
            'storefront_product_uuid'  => $product->storefront_product_uuid,
            'storefront_variant_uuid'  => $variant?->storefront_variant_uuid,
            'storefront_store_uuid'    => $request->input('storefront_store_uuid'),
            'warehouse_uuid'           => $request->input('warehouse_uuid'),
            'total_quantity'           => $totalQuantity,
            'available_quantity'       => $availableQuantity,
            'reserved_quantity'        => $reservedQuantity,
            'out_of_stock'             => $availableQuantity <= 0,
            'low_stock'                => $product->reorder_point !== null && $availableQuantity <= (int) $product->reorder_point,
            'reorder_point'            => (int) $product->reorder_point,
            'reorder_quantity'         => (int) $product->reorder_quantity,
        ];
    }

    protected function availabilityPayload(Request $request, Product $product): array
    {
        $variant = $this->resolveVariant($request, $product);

        if ($this->hasVariantSelector($request) && !$variant) {
            return [
                'available'               => false,
                'available_quantity'      => 0,
                'reserved_quantity'       => 0,
                'total_quantity'          => 0,
                'requested_quantity'      => max(1, (int) $request->input('quantity', 1)),
                'shortage_quantity'       => max(1, (int) $request->input('quantity', 1)),
                'out_of_stock'            => true,
                'low_stock'               => true,
                'product_uuid'            => $product->uuid,
                'variant_uuid'            => null,
                'storefront_product_uuid' => $product->storefront_product_uuid,
                'storefront_variant_uuid' => $request->input('storefront_variant_uuid'),
                'inventory_summary'       => null,
                'message'                 => 'No linked Pallet variant was found.',
            ];
        }

        $summary           = $this->inventorySummary($product, $variant, $request);
        $availableQuantity = $summary['available_quantity'];
        $requestedQuantity = max(1, (int) $request->input('quantity', 1));

        return [
            'available'               => $availableQuantity >= $requestedQuantity,
            'available_quantity'      => $summary['available_quantity'],
            'reserved_quantity'       => $summary['reserved_quantity'],
            'total_quantity'          => $summary['total_quantity'],
            'requested_quantity'      => $requestedQuantity,
            'shortage_quantity'       => max(0, $requestedQuantity - $availableQuantity),
            'out_of_stock'            => (bool) $summary['out_of_stock'],
            'low_stock'               => (bool) $summary['low_stock'],
            'product_uuid'            => $product->uuid,
            'variant_uuid'            => $variant?->uuid,
            'storefront_product_uuid' => $summary['storefront_product_uuid'],
            'storefront_variant_uuid' => $summary['storefront_variant_uuid'],
            'inventory_summary'       => $summary,
            'message'                 => $availableQuantity >= $requestedQuantity ? 'Inventory is available.' : 'Insufficient inventory available for this Storefront product.',
        ];
    }

    protected function createStorefrontReservation(Request $request): InventoryReservation
    {
        $quantity = max(1, (int) $request->input('quantity', 1));
        $product  = $this->resolveProduct($request);

        if (!$product) {
            throw new \RuntimeException('No linked Pallet product was found.', 404);
        }

        $variant = $this->resolveVariant($request, $product);

        if ($this->hasVariantSelector($request) && !$variant) {
            throw new \RuntimeException('No linked Pallet variant was found.', 404);
        }

        $existingReservation = $this->existingStorefrontReservationForKey($request);

        if ($existingReservation) {
            if ((int) $existingReservation->quantity !== $quantity) {
                $existingReservation->release();
            } else {
                return $existingReservation;
            }
        }

        $inventory = $this->inventoryQuery($product, $variant, $request)
            ->where('available_quantity', '>=', $quantity)
            ->orderByRaw('CASE WHEN expiry_date_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date_at')
            ->lockForUpdate()
            ->first();

        if (!$inventory || !$inventory->reserve($quantity)) {
            throw new \RuntimeException('Insufficient inventory available for this Storefront product.', 422);
        }

        return InventoryReservation::create([
            'company_uuid'   => session('company'),
            'product_uuid'   => $product->uuid,
            'variant_uuid'   => $variant?->uuid,
            'inventory_uuid' => $inventory->uuid,
            'warehouse_uuid' => $inventory->warehouse_uuid,
            'order_uuid'     => $request->input('order_uuid') ?? $request->input('storefront_order_uuid'),
            'quantity'       => $quantity,
            'expires_at'     => $request->input('expires_at') ?? now()->addMinutes(30),
            'type'           => 'hard',
            'status'         => 'active',
            'meta'           => [
                'source'                     => 'storefront',
                'storefront_product_uuid'    => $request->input('storefront_product_uuid'),
                'storefront_variant_uuid'    => $request->input('storefront_variant_uuid'),
                'storefront_store_uuid'      => $request->input('storefront_store_uuid'),
                'storefront_cart_uuid'       => $request->input('storefront_cart_uuid'),
                'storefront_checkout_uuid'   => $request->input('storefront_checkout_uuid'),
                'storefront_order_uuid'      => $request->input('storefront_order_uuid'),
                'storefront_line_uuid'       => $request->input('storefront_line_uuid') ?? $request->input('line_uuid') ?? $request->input('line_id'),
                'storefront_reservation_key' => $this->storefrontReservationKey($request),
            ],
        ]);
    }

    protected function existingStorefrontReservationForKey(Request $request): ?InventoryReservation
    {
        $reservationKey = $this->storefrontReservationKey($request);

        if (!$reservationKey) {
            return null;
        }

        InventoryReservation::where('company_uuid', session('company'))
            ->where('status', 'active')
            ->where('meta->source', 'storefront')
            ->where('meta->storefront_reservation_key', $reservationKey)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->lockForUpdate()
            ->get()
            ->each(fn ($reservation) => $reservation->release());

        return InventoryReservation::where('company_uuid', session('company'))
            ->active()
            ->where('meta->source', 'storefront')
            ->where('meta->storefront_reservation_key', $reservationKey)
            ->first();
    }

    protected function storefrontReservationKey(Request $request): ?string
    {
        $explicitKey = $request->input('storefront_reservation_key') ?? $request->input('reservation_key');

        if ($explicitKey) {
            return $explicitKey;
        }

        $checkoutId = $request->input('storefront_checkout_uuid') ?? $request->input('checkout_uuid') ?? $request->input('checkout');
        $cartId     = $request->input('storefront_cart_uuid') ?? $request->input('cart_uuid') ?? $request->input('cart');
        $orderId    = $request->input('storefront_order_uuid') ?? $request->input('order_uuid') ?? $request->input('order');
        $lineId     = $request->input('storefront_line_uuid') ?? $request->input('line_uuid') ?? $request->input('line_id');
        $productId  = $request->input('storefront_product_uuid') ?? $request->input('pallet_product_uuid') ?? $request->input('product_uuid') ?? $request->input('product');
        $variantId  = $request->input('storefront_variant_uuid') ?? $request->input('pallet_variant_uuid') ?? $request->input('variant_uuid') ?? $request->input('variant') ?? 'default';
        $contextId  = $lineId ?: $checkoutId ?: $cartId ?: $orderId;

        if (!$contextId || !$productId) {
            return null;
        }

        return hash('sha256', implode('|', array_filter([
            session('company'),
            $checkoutId,
            $cartId,
            $orderId,
            $lineId,
            $productId,
            $variantId,
        ], fn ($value) => $value !== null && $value !== '')));
    }

    protected function assertStorefrontProductLinkAvailable(string $storefrontProductId, Product $product): void
    {
        $exists = Product::where('company_uuid', session('company'))
            ->where('storefront_product_uuid', $storefrontProductId)
            ->where('uuid', '!=', $product->uuid)
            ->exists();

        if ($exists) {
            throw new \RuntimeException('This Storefront product is already linked to another Pallet product.', 409);
        }
    }

    protected function linkStorefrontVariant(Product $product, mixed $variantLink): void
    {
        $variantLink = is_object($variantLink) ? (array) $variantLink : $variantLink;

        if (!is_array($variantLink)) {
            throw new \RuntimeException('Each Storefront variant link must be an object.', 422);
        }

        $variantId           = $variantLink['pallet_variant_uuid'] ?? $variantLink['variant_uuid'] ?? $variantLink['variant'] ?? null;
        $storefrontVariantId = $variantLink['storefront_variant_uuid'] ?? null;

        if (!$variantId) {
            throw new \RuntimeException('Each variant link requires a Pallet variant UUID.', 422);
        }

        $variant = $this->findVariant($variantId, $product);

        if (!$storefrontVariantId) {
            $variant->storefront_variant_uuid = null;
            $variant->save();

            return;
        }

        $exists = ProductVariant::where('company_uuid', session('company'))
            ->where('storefront_variant_uuid', $storefrontVariantId)
            ->where('uuid', '!=', $variant->uuid)
            ->exists();

        if ($exists) {
            throw new \RuntimeException('This Storefront variant is already linked to another Pallet variant.', 409);
        }

        $variant->storefront_variant_uuid = $storefrontVariantId;
        $variant->save();
    }

    protected function transitionReservationBatch(Request $request, string $transition)
    {
        $ids = $request->input('reservation_uuids', $request->input('reservations', []));
        $ids = is_string($ids) ? collect(explode(',', $ids))->map(fn ($id) => trim($id))->filter()->values() : collect($ids)->filter()->values();

        if ($ids->isEmpty()) {
            return response()->error('At least one reservation UUID is required.', 422);
        }

        try {
            $reservations = DB::transaction(function () use ($ids, $transition) {
                return $ids->map(function ($id) use ($transition) {
                    $reservation = $this->findReservation($id);

                    if (!$reservation->{$transition}()) {
                        throw new \RuntimeException('Reservation cannot be transitioned from its current state.', 422);
                    }

                    return $reservation;
                });
            });

            return InventoryReservationResource::collection($reservations->map->fresh(['product', 'variant', 'inventory', 'warehouse']));
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), $this->statusCodeFromException($e));
        }
    }

    protected function transitionReservationContext(Request $request, string $transition)
    {
        try {
            $reservations = DB::transaction(function () use ($request, $transition) {
                $includeExpired = $transition === 'release';
                $reservations   = $this->storefrontReservationContextQuery($request, $includeExpired)
                    ->lockForUpdate()
                    ->get();

                if ($reservations->isEmpty()) {
                    $label = $transition === 'release' ? 'releasable' : 'committable';
                    throw new \RuntimeException("No {$label} Storefront inventory reservations were found for this context.", 404);
                }

                return $reservations->map(function ($reservation) use ($transition) {
                    if (!$reservation->{$transition}()) {
                        throw new \RuntimeException('Reservation cannot be transitioned from its current state.', 422);
                    }

                    return $reservation;
                });
            });

            return InventoryReservationResource::collection($reservations->map->fresh(['product', 'variant', 'inventory', 'warehouse']));
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), $this->statusCodeFromException($e));
        }
    }

    protected function storefrontReservationContextQuery(Request $request, bool $includeExpired = false)
    {
        $checkoutId     = $request->input('storefront_checkout_uuid') ?? $request->input('checkout_uuid') ?? $request->input('checkout');
        $cartId         = $request->input('storefront_cart_uuid') ?? $request->input('cart_uuid') ?? $request->input('cart');
        $orderId        = $request->input('storefront_order_uuid') ?? $request->input('order_uuid') ?? $request->input('order');
        $lineId         = $request->input('storefront_line_uuid') ?? $request->input('line_uuid') ?? $request->input('line_id');
        $reservationKey = $this->storefrontReservationKey($request);

        if (!$checkoutId && !$cartId && !$orderId && !$lineId && !$reservationKey) {
            throw new \RuntimeException('A Storefront checkout, cart, order, line, or reservation key is required.', 422);
        }

        $query = InventoryReservation::where('company_uuid', session('company'))
            ->where('meta->source', 'storefront')
            ->where('status', 'active');

        if (!$includeExpired) {
            $query->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }

        return $query
            ->when($checkoutId, fn ($query) => $query->where('meta->storefront_checkout_uuid', $checkoutId))
            ->when($cartId, fn ($query) => $query->where('meta->storefront_cart_uuid', $cartId))
            ->when($orderId, fn ($query) => $query->where(fn ($query) => $query->where('order_uuid', $orderId)->orWhere('meta->storefront_order_uuid', $orderId)))
            ->when($lineId, fn ($query) => $query->where('meta->storefront_line_uuid', $lineId))
            ->when($reservationKey, fn ($query) => $query->where('meta->storefront_reservation_key', $reservationKey));
    }

    protected function reservationErrorResponse(\RuntimeException $e, Request $request)
    {
        $status   = $this->statusCodeFromException($e);
        $product  = $this->resolveProduct($request);
        $variant  = $product ? $this->resolveVariant($request, $product) : null;
        $quantity = max(1, (int) $request->input('quantity', 1));
        $summary  = $product && (!$this->hasVariantSelector($request) || $variant) ? $this->inventorySummary($product, $variant, $request) : null;

        return response()->json([
            'error'                   => $e->getMessage(),
            'available_quantity'      => $summary['available_quantity'] ?? 0,
            'requested_quantity'      => $quantity,
            'shortage_quantity'       => $summary ? max(0, $quantity - (int) $summary['available_quantity']) : $quantity,
            'out_of_stock'            => (bool) ($summary['out_of_stock'] ?? true),
            'product_uuid'            => $product?->uuid,
            'variant_uuid'            => $variant?->uuid,
            'storefront_product_uuid' => $summary['storefront_product_uuid'] ?? $request->input('storefront_product_uuid'),
            'storefront_variant_uuid' => $summary['storefront_variant_uuid'] ?? $request->input('storefront_variant_uuid'),
            'inventory_summary'       => $summary,
        ], $status);
    }

    protected function statusCodeFromException(\RuntimeException $e): int
    {
        $code = (int) $e->getCode();

        return $code >= 400 && $code < 600 ? $code : 422;
    }

    public function release(string $id)
    {
        $reservation = $this->findReservation($id);

        if (!$reservation->release()) {
            return response()->error('Reservation cannot be released from its current state.', 422);
        }

        return new InventoryReservationResource($reservation->fresh(['product', 'variant', 'inventory', 'warehouse']));
    }

    public function commit(string $id)
    {
        $reservation = $this->findReservation($id);

        if (!$reservation->fulfill()) {
            return response()->error('Reservation cannot be committed from its current state or inventory is insufficient.', 422);
        }

        return new InventoryReservationResource($reservation->fresh(['product', 'variant', 'inventory', 'warehouse']));
    }

    protected function resolveProduct(Request $request, bool $allowAssistedMatch = false): ?Product
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

        if ($allowAssistedMatch && $request->filled('sku')) {
            return Product::where('company_uuid', session('company'))->where('sku', $request->input('sku'))->first();
        }

        if ($allowAssistedMatch && $request->filled('barcode')) {
            return Product::where('company_uuid', session('company'))->where('barcode', $request->input('barcode'))->first();
        }

        return null;
    }

    protected function resolveVariant(Request $request, Product $product, bool $allowAssistedMatch = false): ?ProductVariant
    {
        $id = $request->input('pallet_variant_uuid') ?? $request->input('variant_uuid') ?? $request->input('variant');

        if ($id) {
            return $product->variants()
                ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
                ->first();
        }

        if ($request->filled('storefront_variant_uuid')) {
            return $product->variants()
                ->where('storefront_variant_uuid', $request->input('storefront_variant_uuid'))
                ->first();
        }

        if ($allowAssistedMatch && $request->filled('variant_sku')) {
            return $product->variants()->where('sku', $request->input('variant_sku'))->first();
        }

        return null;
    }

    protected function hasVariantSelector(Request $request): bool
    {
        return $request->filled('pallet_variant_uuid')
            || $request->filled('variant_uuid')
            || $request->filled('variant')
            || $request->filled('storefront_variant_uuid')
            || $request->filled('variant_sku');
    }

    protected function inventoryQuery(Product $product, ?ProductVariant $variant, Request $request)
    {
        return Inventory::where('company_uuid', session('company'))
            ->where('product_uuid', $product->uuid)
            ->when($variant, fn ($query) => $query->where('variant_uuid', $variant->uuid))
            ->when(!$variant && !$product->has_variants, fn ($query) => $query->whereNull('variant_uuid'))
            ->when($request->filled('warehouse_uuid'), fn ($query) => $query->where('warehouse_uuid', $request->input('warehouse_uuid')))
            ->whereIn('status', ['active', 'available']);
    }

    protected function findProduct(?string $id = null, ?string $storefrontProductUuid = null): Product
    {
        if (!$id && !$storefrontProductUuid) {
            abort(422, 'A Pallet product UUID or Storefront product UUID is required.');
        }

        return Product::where('company_uuid', session('company'))
            ->when($id, fn ($query) => $query->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id)))
            ->when(!$id && $storefrontProductUuid, fn ($query) => $query->where('storefront_product_uuid', $storefrontProductUuid))
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
