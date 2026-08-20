<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * A bin within a warehouse.
 *
 * The older section/aisle/rack hierarchy is reported when a bin is placed in one,
 * but is not settable here — those levels are managed through the console's layout
 * tools rather than assembled a bin at a time over the API.
 */
class BinLocation extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->public_id,
            'object'            => 'bin_location',
            'warehouse'         => data_get($this, 'warehouse.public_id'),
            'warehouse_name'    => data_get($this, 'warehouse.name'),
            'zone'              => data_get($this, 'zone.public_id'),
            'zone_name'         => data_get($this, 'zone.name'),
            'aisle'             => data_get($this, 'aisle.public_id'),
            'rack'              => data_get($this, 'rack.public_id'),
            'section'           => data_get($this, 'section.public_id'),
            'bin_number'        => $this->bin_number,
            'barcode'           => $this->barcode,
            'type'              => $this->type,
            'status'            => $this->status,
            'capacity'          => $this->capacity,
            'current_volume'    => $this->current_volume,
            'dimensions'        => $this->dimensions,
            'is_pickable'       => (bool) $this->is_pickable,
            'is_replenishable'  => (bool) $this->is_replenishable,
            'priority'          => $this->priority,
            'meta'              => $this->meta,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }

    public function toWebhookPayload()
    {
        return $this->toArray(request());
    }
}
