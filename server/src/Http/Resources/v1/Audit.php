<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * An immutable operational audit entry.
 *
 * `subject` is the public id of the record the event happened to, and
 * `subject_type` its short name — the fully qualified PHP class the column stores
 * means nothing outside the application.
 *
 * `old_values`, `new_values` and `meta` are snapshots captured when the event was
 * written, so they carry whatever the model held at the time — including internal
 * uuids. A purchase-order receipt summary alone contributes item_uuid,
 * product_uuid, variant_uuid and inventory_uuid. Emitting them verbatim leaked
 * internal identifiers through the consumable API, which every other resource here
 * is careful not to do, so they are stripped on the way out.
 *
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\Audit
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
            'old_values'   => $this->withoutInternalIds($this->old_values),
            'new_values'   => $this->withoutInternalIds($this->new_values),
            'meta'         => $this->withoutInternalIds($this->meta),
            'created_at'   => $this->created_at,
        ];
    }

    /**
     * Remove uuid-bearing keys at any depth.
     *
     * Keys are dropped rather than blanked: a null `product_uuid` still tells a
     * consumer the column exists and invites them to address records by it.
     */
    protected function withoutInternalIds($value)
    {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        $clean = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && (str_ends_with($key, '_uuid') || $key === 'uuid')) {
                continue;
            }

            $clean[$key] = $this->withoutInternalIds($item);
        }

        return $clean;
    }
}
