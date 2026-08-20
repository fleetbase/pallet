<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * An immutable operational audit entry.
 *
 * `subject` is the public id of the record the event happened to, and
 * `subject_type` its short name — the fully qualified PHP class the column stores
 * means nothing outside the application.
 */
class Audit extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->public_id,
            'object'       => 'audit',
            'event_type'   => $this->event_type,
            'action'       => $this->action,
            'type'         => $this->type,
            'reason'       => $this->reason,
            'comments'     => $this->comments,
            'subject'      => data_get($this, 'auditable.public_id'),
            'subject_type' => $this->auditable_type ? class_basename($this->auditable_type) : null,
            'old_values'   => $this->old_values,
            'new_values'   => $this->new_values,
            'meta'         => $this->meta,
            'created_at'   => $this->created_at,
        ];
    }
}
