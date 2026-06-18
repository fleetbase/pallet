<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class Wave extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                   => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                 => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'            => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'         => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'warehouse_uuid'       => $this->warehouse_uuid,
            'warehouse'            => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),
            'pick_lists'           => $this->whenLoaded('pickLists', fn () => PickList::collection($this->pickLists)),
            'wave_number'          => $this->wave_number,
            'type'                 => $this->type,
            'status'               => $this->status,
            'priority'             => $this->priority,
            'scheduled_at'         => $this->scheduled_at,
            'started_at'           => $this->started_at,
            'completed_at'         => $this->completed_at,
            'total_pick_lists'     => $this->total_pick_lists,
            'completed_pick_lists' => $this->completed_pick_lists,
            'notes'                => $this->notes,
            'meta'                 => $this->meta ?? [],
            'updated_at'           => $this->updated_at,
            'created_at'           => $this->created_at,
        ];
    }
}
