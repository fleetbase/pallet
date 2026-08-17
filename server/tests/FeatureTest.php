<?php

use Fleetbase\Pallet\Http\Controllers\StorefrontInventoryController;
use Fleetbase\Pallet\Http\Resources\Product as ProductResource;
use Fleetbase\Pallet\Http\Resources\ProductVariant as ProductVariantResource;
use Fleetbase\Pallet\Models\BinLocation;
use Fleetbase\Pallet\Models\CycleCount;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\InventoryReservation;
use Fleetbase\Pallet\Models\PickList;
use Fleetbase\Pallet\Models\PickListItem;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Fleetbase\Pallet\Models\StockTransfer;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseZone;
use Fleetbase\Pallet\Models\Wave;
use Illuminate\Http\Request;

test('pallet products are first class product records', function () {
    expect((new Product())->getTable())->toBe('pallet_products');
    expect((new ProductVariant())->getTable())->toBe('pallet_product_variants');
    expect((new Product())->variants()->getForeignKeyName())->toBe('product_uuid');
    expect((new Product())->variants()->getLocalKeyName())->toBe('uuid');
});

test('inventory core relationships resolve by uuid owner keys', function () {
    $inventory = new Inventory();

    expect($inventory->product()->getOwnerKeyName())->toBe('uuid');
    expect($inventory->variant()->getOwnerKeyName())->toBe('uuid');
    expect($inventory->warehouse()->getOwnerKeyName())->toBe('uuid');
    expect($inventory->batch()->getOwnerKeyName())->toBe('uuid');
    expect($inventory->binLocation()->getOwnerKeyName())->toBe('uuid');
    expect($inventory->zone()->getOwnerKeyName())->toBe('uuid');
});

test('wms aggregate relationships resolve by uuid local keys', function () {
    $warehouse = new Warehouse();
    $zone      = new WarehouseZone();
    $wave      = new Wave();
    $pickList  = new PickList();

    expect($warehouse->zones()->getLocalKeyName())->toBe('uuid');
    expect($warehouse->binLocations()->getLocalKeyName())->toBe('uuid');
    expect($zone->binLocations()->getLocalKeyName())->toBe('uuid');
    expect($wave->pickLists()->getLocalKeyName())->toBe('uuid');
    expect($pickList->items()->getLocalKeyName())->toBe('uuid');
});

test('wms item relationships resolve by uuid owner keys', function () {
    $reservation = new InventoryReservation();
    $pickItem    = new PickListItem();
    $cycleCount  = new CycleCount();
    $binLocation = new BinLocation();
    $transfer    = new StockTransfer();

    expect($reservation->inventory()->getOwnerKeyName())->toBe('uuid');
    expect($reservation->warehouse()->getOwnerKeyName())->toBe('uuid');
    expect($pickItem->inventory()->getOwnerKeyName())->toBe('uuid');
    expect($pickItem->binLocation()->getOwnerKeyName())->toBe('uuid');
    expect($cycleCount->items()->getLocalKeyName())->toBe('uuid');
    expect($binLocation->inventoryItems()->getLocalKeyName())->toBe('uuid');
    expect($transfer->items()->getLocalKeyName())->toBe('uuid');
});

test('inventory available quantity is derived from quantity minus reserved quantity', function () {
    $inventory                    = new Inventory();
    $inventory->quantity          = 12;
    $inventory->reserved_quantity = 5;

    $inventory->syncAvailableQuantity();

    expect($inventory->available_quantity)->toBe(7);
});

test('inventory reserved quantity cannot exceed on hand quantity', function () {
    $inventory                    = new Inventory();
    $inventory->quantity          = 4;
    $inventory->reserved_quantity = 9;

    $inventory->syncAvailableQuantity();

    expect($inventory->reserved_quantity)->toBe(4);
    expect($inventory->available_quantity)->toBe(0);
});

test('inventory cannot commit more reserved stock than it holds', function () {
    $inventory                     = new Inventory();
    $inventory->quantity           = 6;
    $inventory->reserved_quantity  = 2;
    $inventory->available_quantity = 4;

    expect($inventory->commitReserved(3))->toBeFalse();
});

test('products reject non-positive inventory reservations before stock lookup', function () {
    $product = new Product();

    expect($product->reserveInventory(0, 'order_123'))->toBeNull();
});

test('completed pick lists cannot be restarted', function () {
    $pickList         = new PickList();
    $pickList->status = 'completed';

    expect(fn () => $pickList->start())->toThrow(RuntimeException::class);
});

test('waves must be released before completing', function () {
    $wave         = new Wave();
    $wave->status = 'released';

    expect(fn () => $wave->complete())->toThrow(RuntimeException::class);
});

test('cycle counts must be completed before approval', function () {
    $cycleCount         = new CycleCount();
    $cycleCount->status = 'in_progress';

    expect(fn () => $cycleCount->approve())->toThrow(RuntimeException::class);
});

test('product resources expose storefront inventory summary contract', function () {
    $product = new Product([
        'storefront_product_uuid' => 'storefront-product-123',
        'reorder_point'           => 5,
        'reorder_quantity'        => 12,
    ]);

    $product->uuid = 'pallet-product-123';

    $resource = (new ProductResource($product))->toArray(request());

    expect($resource['inventory_summary'])->toMatchArray([
        'product_uuid'            => 'pallet-product-123',
        'variant_uuid'            => null,
        'storefront_product_uuid' => 'storefront-product-123',
        'storefront_variant_uuid' => null,
        'reorder_point'           => 5,
        'reorder_quantity'        => 12,
    ]);
});

test('product variant resources expose storefront inventory summary contract', function () {
    $product = new Product([
        'storefront_product_uuid' => 'storefront-product-123',
        'reorder_point'           => 5,
        'reorder_quantity'        => 12,
    ]);
    $product->uuid = 'pallet-product-123';

    $variant = new ProductVariant([
        'product_uuid'             => 'pallet-product-123',
        'storefront_variant_uuid'  => 'storefront-variant-123',
    ]);
    $variant->uuid = 'pallet-variant-123';
    $variant->setRelation('product', $product);

    $resource = (new ProductVariantResource($variant))->toArray(request());

    expect($resource['inventory_summary'])->toMatchArray([
        'product_uuid'            => 'pallet-product-123',
        'variant_uuid'            => 'pallet-variant-123',
        'storefront_product_uuid' => 'storefront-product-123',
        'storefront_variant_uuid' => 'storefront-variant-123',
        'reorder_point'           => 5,
        'reorder_quantity'        => 12,
    ]);
});

test('inventory reservation resources expose generic order uuid for storefront reservations', function () {
    $reservation = new InventoryReservation([
        'order_uuid'       => 'storefront-order-123',
        'sales_order_uuid' => null,
        'quantity'         => 2,
        'status'           => 'active',
        'type'             => 'hard',
        'meta'             => [
            'storefront_checkout_uuid'   => 'checkout-123',
            'storefront_line_uuid'       => 'line-123',
            'storefront_reservation_key' => 'checkout-123:line-123',
        ],
    ]);

    $resource = (new Fleetbase\Pallet\Http\Resources\InventoryReservation($reservation))->toArray(request());

    expect($resource['order_uuid'])->toBe('storefront-order-123');
    expect($resource['storefront_checkout_uuid'])->toBe('checkout-123');
    expect($resource['storefront_line_uuid'])->toBe('line-123');
    expect($resource['storefront_reservation_key'])->toBe('checkout-123:line-123');
});

test('storefront reservation context requires a checkout cart order line or reservation reference', function () {
    $controller = new class extends StorefrontInventoryController {
        public function exposeContextQuery(Request $request)
        {
            return $this->storefrontReservationContextQuery($request);
        }
    };

    expect(fn () => $controller->exposeContextQuery(new Request()))->toThrow(RuntimeException::class);
});

test('storefront reservation key accepts explicit key aliases', function () {
    $controller = new class extends StorefrontInventoryController {
        public function exposeReservationKey(Request $request): ?string
        {
            return $this->storefrontReservationKey($request);
        }
    };

    expect($controller->exposeReservationKey(new Request(['storefront_reservation_key' => 'checkout-line-1'])))->toBe('checkout-line-1');
    expect($controller->exposeReservationKey(new Request(['reservation_key' => 'checkout-line-2'])))->toBe('checkout-line-2');
});

test('storefront reservation context query can include expired active reservations for release', function () {
    $controller = new class extends StorefrontInventoryController {
        public function exposeContextQuery(Request $request, bool $includeExpired = false)
        {
            return $this->storefrontReservationContextQuery($request, $includeExpired);
        }
    };

    $query = $controller->exposeContextQuery(new Request(['storefront_reservation_key' => 'checkout-line-1']), true);

    expect(collect($query->getQuery()->wheres)->contains(fn ($where) => data_get($where, 'column') === 'expires_at'))->toBeFalse();
});

test('storefront product links reject invalid variant link payloads before partial save', function () {
    $controller = new class extends StorefrontInventoryController {
        public function exposeLinkVariant(Product $product, mixed $variantLink): void
        {
            $this->linkStorefrontVariant($product, $variantLink);
        }
    };

    $product       = new Product();
    $product->uuid = 'pallet-product-123';

    expect(fn () => $controller->exposeLinkVariant($product, 'not-an-object'))->toThrow(RuntimeException::class);
    expect(fn () => $controller->exposeLinkVariant($product, ['pallet_variant_uuid' => 'variant_123']))->toThrow(RuntimeException::class);
});
