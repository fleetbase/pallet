<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class CycleCount extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'           => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'        => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'warehouse_uuid'      => $this->warehouse_uuid,
            'zone_uuid'           => $this->zone_uuid,
            'assigned_to_uuid'    => $this->assigned_to_uuid,
            'warehouse'           => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),
            'zone'                => $this->whenLoaded('zone', fn () => new WarehouseZone($this->zone)),
            'assigned_to'         => $this->whenLoaded('assignedTo', $this->assignedTo),
            'items'               => $this->whenLoaded('items', fn () => CycleCountItem::collection($this->items)),
            'count_number'        => $this->count_number,
            'type'                => $this->type,
            'status'              => $this->status,
            'scheduled_at'        => $this->scheduled_at,
            'started_at'          => $this->started_at,
            'completed_at'        => $this->completed_at,
            'total_items'         => $this->total_items,
            'counted_items'       => $this->counted_items,
            'discrepancies_count' => $this->discrepancies_count,
            'accuracy_percentage' => $this->accuracy_percentage,
            'notes'               => $this->notes,
            'meta'                => $this->meta ?? [],
            'updated_at'          => $this->updated_at,
            'created_at'          => $this->created_at,
        ];
    }
}
