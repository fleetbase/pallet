<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class Audit extends FleetbaseResource
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
            'id'                => $this->when(Http::isInternalRequest(), $this->incrementing_id, $this->public_id),
            'uuid'              => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'         => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'      => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'performed_by_uuid' => $this->performed_by_uuid,
            'performed_by'      => $this->whenLoaded('performedBy', $this->performedBy),
            'auditable_uuid'    => $this->auditable_uuid,
            'auditable_type'    => $this->auditable_type,
            'action'            => $this->action,
            'type'              => $this->type,
            'reason'            => $this->reason,
            'comments'          => $this->comments,
            'meta'              => $this->meta ?? [],
            'old_values'        => $this->old_values ?? [],
            'new_values'        => $this->new_values ?? [],
            'scheduled_at'      => $this->scheduled_at,
            'completed_at'      => $this->completed_at,
            'updated_at'        => $this->updated_at,
            'created_at'        => $this->created_at,
        ];
    }
}
