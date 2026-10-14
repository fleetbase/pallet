<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\Supplier
 */
class Supplier extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->public_id,
            'object'      => 'supplier',
            'internal_id' => $this->internal_id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'type'        => $this->type,
            'status'      => $this->status,
            'country'     => $this->country,
            'website_url' => $this->website_url,
            'address'     => data_get($this, 'place.address'),
            'meta'        => $this->meta,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }

    public function toWebhookPayload()
    {
        return $this->toArray(request());
    }
}
