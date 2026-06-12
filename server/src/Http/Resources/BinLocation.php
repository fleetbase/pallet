<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class BinLocation extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                    => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                  => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'             => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'          => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'warehouse_uuid'        => $this->warehouse_uuid,
            'zone_uuid'             => $this->zone_uuid,
            'aisle_uuid'            => $this->aisle_uuid,
            'rack_uuid'             => $this->rack_uuid,
            'section_uuid'          => $this->section_uuid,
            'warehouse'             => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),
            'zone'                  => $this->whenLoaded('zone', fn () => new WarehouseZone($this->zone)),
            'bin_number'            => $this->bin_number,
            'barcode'               => $this->barcode,
            'type'                  => $this->type,
            'status'                => $this->status,
            'capacity'              => $this->capacity,
            'current_volume'        => $this->current_volume,
            'available_capacity'    => $this->available_capacity,
            'utilization_percentage'=> $this->utilization_percentage,
            'dimensions'            => $this->dimensions ?? [],
            'is_pickable'           => $this->is_pickable,
            'is_replenishable'      => $this->is_replenishable,
            'priority'              => $this->priority,
            'meta'                  => $this->meta ?? [],
            'updated_at'            => $this->updated_at,
            'created_at'            => $this->created_at,
        ];
    }
}
