<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class PickList extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                    => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                  => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'             => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'          => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'warehouse_uuid'        => $this->warehouse_uuid,
            'sales_order_uuid'      => $this->sales_order_uuid,
            'wave_uuid'             => $this->wave_uuid,
            'assigned_to_uuid'      => $this->assigned_to_uuid,
            'warehouse'             => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),
            'items'                 => $this->whenLoaded('items', fn () => PickListItem::collection($this->items)),
            'pick_list_number'      => $this->pick_list_number,
            'type'                  => $this->type,
            'priority'              => $this->priority,
            'status'                => $this->status,
            'started_at'            => $this->started_at,
            'completed_at'          => $this->completed_at,
            'total_items'           => $this->total_items,
            'picked_items'          => $this->picked_items,
            'completion_percentage' => $this->completion_percentage,
            'notes'                 => $this->notes,
            'meta'                  => $this->meta ?? [],
            'updated_at'            => $this->updated_at,
            'created_at'            => $this->created_at,
        ];
    }
}
