<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * Consumable representation of a warehouse.
 *
 * The address is flattened out of the related place rather than exposed as a
 * place uuid — a consumer of this API has no way to resolve one.
 */
class Warehouse extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->public_id,
            'object'              => 'warehouse',
            'name'                => $this->name,
            'code'                => $this->code,
            'type'                => $this->type,
            'status'              => $this->status,
            'capacity'            => $this->capacity,
            'current_utilization' => $this->current_utilization,
            'floor_area_sqm'      => $this->floor_area_sqm,
            'operating_hours'     => $this->operating_hours,
            'timezone'            => $this->timezone,
            'phone'               => $this->phone,
            'email'               => $this->email,
            'total_docks'         => $this->total_docks,
            'is_active'           => (bool) $this->is_active,
            'is_default'          => (bool) $this->is_default,
            'stock_item_count'    => $this->when(isset($this->inventories_count), fn () => (int) $this->inventories_count),
            'address'             => data_get($this, 'place.address'),
            'meta'                => $this->meta,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }

    public function toWebhookPayload()
    {
        return $this->toArray(request());
    }
}
