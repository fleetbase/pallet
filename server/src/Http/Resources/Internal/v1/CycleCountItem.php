<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class CycleCountItem extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'              => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'         => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'      => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'cycle_count_uuid'  => $this->cycle_count_uuid,
            'product_uuid'      => $this->product_uuid,
            'variant_uuid'      => $this->variant_uuid,
            'inventory_uuid'    => $this->inventory_uuid,
            'bin_location_uuid' => $this->bin_location_uuid,
            'product'           => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant'           => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'inventory'         => $this->whenLoaded('inventory', fn () => new Inventory($this->inventory)),
            'bin_location'      => $this->whenLoaded('binLocation', fn () => new BinLocation($this->binLocation)),
            'expected_quantity' => (int) $this->expected_quantity,
            'counted_quantity'  => (int) $this->counted_quantity,
            'variance'          => (int) $this->variance,
            'has_discrepancy'   => $this->has_discrepancy,
            'status'            => $this->status,
            'counted_at'        => $this->counted_at,
            'counted_by_uuid'   => $this->counted_by_uuid,
            'lot_number'        => $this->lot_number,
            'serial_number'     => $this->serial_number,
            'notes'             => $this->notes,
            'meta'              => $this->meta ?? [],
            'updated_at'        => $this->updated_at,
            'created_at'        => $this->created_at,
        ];
    }
}
