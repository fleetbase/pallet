<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

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
