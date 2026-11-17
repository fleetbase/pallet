<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\StockAdjustment
 */
class StockAdjustment extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'                        => $this->when(Http::isInternalRequest(), $this->incrementing_id, $this->public_id),
            'uuid'                      => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'                 => $this->when(Http::isInternalRequest(), $this->public_id),
            'product_uuid'              => $this->product_uuid,
            'variant_uuid'              => $this->variant_uuid,
            'inventory_uuid'            => $this->inventory_uuid,
            'warehouse_uuid'            => $this->warehouse_uuid,
            'product'                   => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant'                   => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'inventory'                 => $this->whenLoaded('inventory', fn () => new Inventory($this->inventory)),
            'warehouse'                 => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),
            'assignee_uuid'             => $this->assignee_uuid,
            'reason'                    => $this->reason,
            'type'                      => $this->type,
            'approval_required'         => $this->approval_required,
            'quantity'                  => (int) $this->quantity,
            'before_quantity'           => (int) $this->before_quantity,
            'after_quantity'            => (int) $this->after_quantity,
            'updated_at'                => $this->updated_at,
            'created_at'                => $this->created_at,
        ];
    }
}
