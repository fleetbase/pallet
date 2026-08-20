<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Pallet\Http\Resources\WarehouseRack as WarehouseRackResource;
use Fleetbase\Support\Http;

class WarehouseAisle extends FleetbaseResource
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
            'section_uuid'         => $this->section_uuid,
            'zone_uuid'            => $this->zone_uuid,
            'aisle_number'         => $this->aisle_number,
            'area'                 => $this->area,
            'meta'                 => $this->meta ?? [],
            'racks'                => WarehouseRackResource::collection($this->racks),
            'updated_at'           => $this->updated_at,
            'created_at'           => $this->created_at,
        ];
    }
}
