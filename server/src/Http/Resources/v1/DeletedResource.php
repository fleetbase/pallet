<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Utils;

/**
 * Confirmation body returned when a consumable-API delete succeeds.
 */
class DeletedResource extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'      => $this->public_id,
            'object'  => $this->getObjectType(),
            'deleted' => true,
            'time'    => $this->deleted_at,
        ];
    }

    public function getObjectType(): string
    {
        return Utils::getTypeFromClassName($this->resource);
    }
}
