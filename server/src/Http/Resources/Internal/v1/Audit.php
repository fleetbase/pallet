<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\Audit
 */
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

            // Who performed the action
            'performed_by_uuid' => $this->performed_by_uuid,
            'performed_by'      => $this->whenLoaded('performedBy', fn () => $this->performedBy ? [
                'uuid'   => $this->performedBy->uuid,
                'name'   => $this->performedBy->name,
                'email'  => $this->performedBy->email,
                'avatar' => $this->performedBy->avatar_url ?? null,
            ] : null),

            // Who created the record (usually same as performed_by)
            'created_by_uuid'   => $this->when(Http::isInternalRequest(), $this->created_by_uuid),
            'created_by'        => $this->whenLoaded('createdBy', fn () => $this->createdBy ? [
                'uuid'  => $this->createdBy->uuid,
                'name'  => $this->createdBy->name,
            ] : null),

            // Polymorphic subject
            'auditable_uuid'    => $this->auditable_uuid,
            'auditable_type'    => $this->auditable_type,
            'subject_uuid'      => $this->auditable_uuid,
            'subject_type'      => $this->auditable_type,
            'subject_label'     => $this->subject_label,
            'subject_reference' => $this->subject_reference,
            'description'       => $this->action,

            // Event classification
            'event_type'        => $this->event_type,
            'action'            => $this->action,
            'type'              => $this->type,

            // Operational context
            'reason'            => $this->reason,
            'comments'          => $this->comments,
            'meta'              => $this->meta ?? [],
            'old_values'        => $this->old_values ?? [],
            'new_values'        => $this->new_values ?? [],

            // Timestamps
            'scheduled_at'      => $this->scheduled_at,
            'completed_at'      => $this->completed_at,
            'updated_at'        => $this->updated_at,
            'created_at'        => $this->created_at,
            'performed_by_name' => $this->performedBy?->name,
        ];
    }
}
