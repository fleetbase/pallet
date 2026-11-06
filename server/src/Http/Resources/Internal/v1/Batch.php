<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\Batch
 */
class Batch extends FleetbaseResource
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
            'company_uuid'              => $this->company_uuid,
            'created_by_uuid'           => $this->created_by_uuid,
            'product_uuid'              => $this->product_uuid,
            'variant_uuid'              => $this->variant_uuid,
            'product'                   => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant'                   => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'batch_number'              => $this->batch_number,
            'quantity'                  => $this->quantity,
            'meta'                      => $this->meta ?? [],
            'updated_at'                => $this->updated_at,
            'created_at'                => $this->created_at,
            'expiry_date_at'            => $this->expiry_date_at,
            'manufacture_date_at'       => $this->manufacture_date_at,
        ];
    }
}
