<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

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
            'id'                        => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                      => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'                 => $this->when(Http::isInternalRequest(), $this->public_id),
            'product_uuid'              => $this->product_uuid,
            'product'                   => $this->whenLoaded('product', $this->product),
            'quantity'                  => (int) $this->quantity,
            'before_quantity'           => (int) $this->before_quantity,
            'after_quantity'            => (int) $this->after_quantity,
            'updated_at'                => $this->updated_at,
            'created_at'                => $this->created_at,
        ];
    }
}
