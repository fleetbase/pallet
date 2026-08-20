<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Pallet\Http\Resources\WarehouseBin as WarehouseBinResource;
use Fleetbase\Support\Http;

class WarehouseRack extends FleetbaseResource
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
            'id'                   => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                 => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'            => $this->when(Http::isInternalRequest(), $this->public_id),
            'warehouse_uuid'       => $this->warehouse_uuid,
            'aisle_uuid'           => $this->aisle_uuid,
            'bins'                 => WarehouseBinResource::collection($this->bins),
            'capacity'             => $this->capacity,
            'rack_number'          => $this->rack_number,
            'meta'                 => $this->meta ?? [],
            'updated_at'           => $this->updated_at,
            'created_at'           => $this->created_at,
        ];
    }
}
