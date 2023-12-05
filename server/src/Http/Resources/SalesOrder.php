<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Pallet\Models\WarehouseRack;
use Fleetbase\Support\Http;

class SalesOrder extends FleetbaseResource
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
            'supplier_uuid'             => $this->supplier_uuid,
            'status'                    => $this->status,
            'comments'                  => $this->comments,
            'description'               => $this->description,
            'reference_code'            => $this->reference_code,
            'reference_url'             => $this->reference_url,
            'customer_reference_code'   => $this->customer_reference_code,
            'order_date_at'             => $this->orde_date_at,
            'expected_delivery_at'      => $this->expected_delivery_at,
            'updated_at'                => $this->updated_at,
            'created_at'                => $this->created_at,
        ];
    }
}
