<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\WarehouseZone
 */
class WarehouseZone extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                     => $this->public_id,
            'object'                 => 'warehouse_zone',
            'warehouse'              => data_get($this, 'warehouse.public_id'),
            'warehouse_name'         => data_get($this, 'warehouse.name'),
            'name'                   => $this->name,
            'code'                   => $this->code,
            'type'                   => $this->type,
            'status'                 => $this->status,
            'temperature_controlled' => (bool) $this->temperature_controlled,
            'temperature_range'      => $this->temperature_range,
            'capacity'               => $this->capacity,
            'current_utilization'    => $this->current_utilization,
            'meta'                   => $this->meta,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }

    public function toWebhookPayload()
    {
        return $this->toArray(request());
    }
}
