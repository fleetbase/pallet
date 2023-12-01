<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;
use Fleetbase\Support\Resolve;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class Warehouse extends FleetbaseResource
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
            'id'                   => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                 => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'            => $this->when(Http::isInternalRequest(), $this->public_id),
            'name'                 => $this->name,
            'location'             => data_get($this, 'location', new Point(0, 0)),
            'address'              => $this->address,
            'address_html'         => $this->when(Http::isInternalRequest(), $this->address_html),
            'street1'              => data_get($this, 'street1'),
            'street2'              => data_get($this, 'street2'),
            'city'                 => data_get($this, 'city'),
            'province'             => data_get($this, 'province'),
            'postal_code'          => data_get($this, 'postal_code'),
            'neighborhood'         => data_get($this, 'neighborhood'),
            'district'             => data_get($this, 'district'),
            'building'             => data_get($this, 'building'),
            'security_access_code' => data_get($this, 'security_access_code'),
            'country'              => data_get($this, 'country'),
            'country_name'         => $this->when(Http::isInternalRequest(), $this->country_name),
            'phone'                => data_get($this, 'phone'),
            'owner'                => $this->when(!Http::isInternalRequest(), Resolve::resourceForMorph($this->owner_type, $this->owner_uuid)),
            'tracking_number'      => $this->whenLoaded('trackingNumber', $this->trackingNumber),
            'type'                 => data_get($this, 'type'),
            'meta'                 => data_get($this, 'meta', []),
            'sections'             => $this->whenLoaded('sections', $this->sections),
            'docks'                => $this->whenLoaded('docks', $this->docks),
            'updated_at'           => $this->updated_at,
            'created_at'           => $this->created_at,
        ];
    }

    /**
     * Transform the resource into an webhook payload.
     *
     * @return array
     */
    public function toWebhookPayload()
    {
        return [
            'id'                   => $this->public_id,
            'internal_id'          => $this->internal_id,
            'name'                 => $this->name,
            'latitude'             => $this->latitude ?? null,
            'longitude'            => $this->longitude ?? null,
            'street1'              => $this->street1 ?? null,
            'street2'              => $this->street2 ?? null,
            'city'                 => $this->city ?? null,
            'province'             => $this->province ?? null,
            'postal_code'          => $this->postal_code ?? null,
            'neighborhood'         => $this->neighborhood ?? null,
            'district'             => $this->district ?? null,
            'building'             => $this->building ?? null,
            'security_access_code' => $this->security_access_code ?? null,
            'country'              => $this->country ?? null,
            'phone'                => $this->phone ?? null,
            'owner'                => Resolve::resourceForMorph($this->owner_type, $this->owner_uuid),
            'type'                 => $this->type ?? null,
            'meta'                 => $this->meta ?? [],
            'sections'             => $this->sections ?? [],
            'updated_at'           => $this->updated_at,
            'created_at'           => $this->created_at,
        ];
    }
}
