<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\InventoryReservation
 */
class InventoryReservation extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                         => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                       => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'                  => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'               => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'product_uuid'               => $this->product_uuid,
            'variant_uuid'               => $this->variant_uuid,
            'inventory_uuid'             => $this->inventory_uuid,
            'warehouse_uuid'             => $this->warehouse_uuid,
            'order_uuid'                 => $this->order_uuid,
            'sales_order_uuid'           => $this->sales_order_uuid,
            'pick_list_uuid'             => $this->pick_list_uuid,
            'storefront_product_uuid'    => data_get($this->meta, 'storefront_product_uuid'),
            'storefront_variant_uuid'    => data_get($this->meta, 'storefront_variant_uuid'),
            'storefront_store_uuid'      => data_get($this->meta, 'storefront_store_uuid'),
            'storefront_cart_uuid'       => data_get($this->meta, 'storefront_cart_uuid'),
            'storefront_checkout_uuid'   => data_get($this->meta, 'storefront_checkout_uuid'),
            'storefront_order_uuid'      => data_get($this->meta, 'storefront_order_uuid'),
            'storefront_line_uuid'       => data_get($this->meta, 'storefront_line_uuid'),
            'storefront_reservation_key' => data_get($this->meta, 'storefront_reservation_key'),
            'product'                    => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant'                    => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'inventory'                  => $this->whenLoaded('inventory', fn () => new Inventory($this->inventory)),
            'warehouse'                  => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),
            /*
             * A reference, not the SalesOrder resource. That resource renders its line
             * items through `whenLoaded('items', $this->items ?? [])`, whose second
             * argument PHP evaluates before whenLoaded can decide anything — so it
             * lazy-loads the items and emits a full order tree per row. The list needs
             * an order number and somewhere to click; this is that and nothing more.
             *
             * Null-guarded rather than left to whenLoaded: the relation is eager-loaded
             * on every reservation, and most reservations have no sales order at all
             * (storefront holds do not), so "loaded" here routinely means "loaded, and
             * it is null".
             */
            'sales_order'                => $this->when($this->salesOrder !== null, fn () => [
                // `uuid` is what decides identity — ember-core's ApplicationSerializer
                // sets primaryKey = 'uuid' — so an order embedded here merges onto the
                // same store record as one loaded from the sales order list instead of
                // becoming a second copy. `id` is carried only because every other
                // resource in this namespace emits it, under the same expression.
                'id'           => Http::isInternalRequest() ? $this->salesOrder->id : $this->salesOrder->public_id,
                'uuid'         => $this->salesOrder->uuid,
                'public_id'    => $this->salesOrder->public_id,
                'order_number' => $this->salesOrder->order_number,
                'status'       => $this->salesOrder->status,
            ]),
            'quantity'                   => (int) $this->quantity,
            'reserved_at'                => $this->reserved_at,
            'expires_at'                 => $this->expires_at,
            'released_at'                => $this->released_at,
            'status'                     => $this->status,
            'type'                       => $this->type,
            'is_expired'                 => $this->is_expired,
            'is_active'                  => $this->is_active,
            'meta'                       => $this->meta ?? [],
            'updated_at'                 => $this->updated_at,
            'created_at'                 => $this->created_at,
        ];
    }
}
