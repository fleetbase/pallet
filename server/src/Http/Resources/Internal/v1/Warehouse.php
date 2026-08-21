<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

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
        // Resolve the linked Place for address/location fields
        $place = $this->place;

        return [
            // Identity
            'id'                     => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                   => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'              => $this->when(Http::isInternalRequest(), $this->public_id),

            // Warehouse-specific fields
            'name'                   => $this->name,
            'code'                   => $this->code,
            'type'                   => $this->type,
            'status'                 => $this->status,
            'capacity'               => $this->capacity,
            'current_utilization'    => $this->current_utilization,
            'utilization_percentage' => $this->utilization_percentage,
            'floor_area_sqm'         => $this->floor_area_sqm,
            'operating_hours'        => $this->operating_hours ?? [],
            'timezone'               => $this->timezone,
            'phone'                  => $this->phone,
            'email'                  => $this->email,
            'total_docks'            => $this->total_docks,
            'stock_items'            => (int) ($this->inventories_count ?? 0),
            'is_active'              => $this->is_active,
            'is_default'             => $this->is_default,
            'meta'                   => $this->meta ?? [],

            // Place / address fields (proxied from the linked Place)
            'place_uuid'             => $this->when(Http::isInternalRequest(), $this->place_uuid),
            'manager_uuid'           => $this->manager_uuid,
            'manager'                => $this->whenLoaded('manager', fn () => $this->manager ? [
                'uuid' => $this->manager->uuid,
                'name' => $this->manager->name,
            ] : null),
            'place'                  => $this->whenLoaded('place', $place),
            'address'                => $this->address,
            'address_html'           => $this->when(Http::isInternalRequest(), $place?->address_html),
            'location'               => $place?->location,
            'street1'                => $place?->street1,
            'street2'                => $place?->street2,
            'city'                   => $place?->city,
            'province'               => $place?->province,
            'postal_code'            => $place?->postal_code,
            'neighborhood'           => $place?->neighborhood,
            'district'               => $place?->district,
            'building'               => $place?->building,
            'country'                => $place?->country,
            'country_name'           => $this->when(Http::isInternalRequest(), $place?->country_name),
            'latitude'               => $place?->latitude ?? null,
            'longitude'              => $place?->longitude ?? null,

            // Sub-resources
            'sections'               => $this->whenLoaded('sections', $this->sections, []),
            'zones'                  => $this->whenLoaded('zones', $this->zones, []),
            'docks'                  => $this->whenLoaded('docks', $this->docks, []),
            'total_zones'            => $this->total_zones,
            'total_bins'             => $this->total_bins,

            // Timestamps
            'updated_at'             => $this->updated_at,
            'created_at'             => $this->created_at,
        ];
    }

    /**
     * Transform the resource into an webhook payload.
     *
     * @return array
     */
    public function toWebhookPayload()
    {
        $place = $this->place;

        return [
            'id'                  => $this->public_id,
            'name'                => $this->name,
            'code'                => $this->code,
            'type'                => $this->type,
            'status'              => $this->status,
            'capacity'            => $this->capacity,
            'current_utilization' => $this->current_utilization,
            'is_active'           => $this->is_active,
            'latitude'            => $place?->latitude ?? null,
            'longitude'           => $place?->longitude ?? null,
            'street1'             => $place?->street1 ?? null,
            'street2'             => $place?->street2 ?? null,
            'city'                => $place?->city ?? null,
            'province'            => $place?->province ?? null,
            'postal_code'         => $place?->postal_code ?? null,
            'country'             => $place?->country ?? null,
            'phone'               => $this->phone ?? null,
            'email'               => $this->email ?? null,
            'meta'                => $this->meta ?? [],
            'sections'            => $this->sections ?? [],
            'updated_at'          => $this->updated_at,
            'created_at'          => $this->created_at,
        ];
    }
}
