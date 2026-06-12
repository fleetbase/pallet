<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class WarehouseZone extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                     => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                   => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'              => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'           => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'warehouse_uuid'         => $this->warehouse_uuid,
            'warehouse'              => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),
            'name'                   => $this->name,
            'code'                   => $this->code,
            'type'                   => $this->type,
            'status'                 => $this->status,
            'temperature_controlled' => $this->temperature_controlled,
            'temperature_range'      => $this->temperature_range,
            'capacity'               => $this->capacity,
            'current_utilization'    => $this->current_utilization,
            'utilization_percentage' => $this->utilization_percentage,
            'meta'                   => $this->meta ?? [],
            'updated_at'             => $this->updated_at,
            'created_at'             => $this->created_at,
        ];
    }
}
