<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\PickListItem
 */
class PickListItem extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                    => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                  => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'             => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'          => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'pick_list_uuid'        => $this->pick_list_uuid,
            'product_uuid'          => $this->product_uuid,
            'variant_uuid'          => $this->variant_uuid,
            'inventory_uuid'        => $this->inventory_uuid,
            'bin_location_uuid'     => $this->bin_location_uuid,
            'sales_order_item_uuid' => $this->sales_order_item_uuid,
            'product'               => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant'               => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'inventory'             => $this->whenLoaded('inventory', fn () => new Inventory($this->inventory)),
            'bin_location'          => $this->whenLoaded('binLocation', fn () => new BinLocation($this->binLocation)),
            'quantity_requested'    => (int) $this->quantity_requested,
            'quantity_picked'       => (int) $this->quantity_picked,
            'sequence_number'       => (int) $this->sequence_number,
            'status'                => $this->status,
            'picked_at'             => $this->picked_at,
            'picked_by_uuid'        => $this->picked_by_uuid,
            'lot_number'            => $this->lot_number,
            'serial_number'         => $this->serial_number,
            'notes'                 => $this->notes,
            'meta'                  => $this->meta ?? [],
            'updated_at'            => $this->updated_at,
            'created_at'            => $this->created_at,
        ];
    }
}
