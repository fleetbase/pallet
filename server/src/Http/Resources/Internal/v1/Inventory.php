<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class Inventory extends FleetbaseResource
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
            'id'                    => $this->when(Http::isInternalRequest(), $this->incrementing_id, $this->public_id),
            'uuid'                  => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'             => $this->when(Http::isInternalRequest(), $this->public_id),
            'product_uuid'          => $this->product_uuid,
            'variant_uuid'          => $this->variant_uuid,
            'batch_uuid'            => $this->batch_uuid,
            'warehouse_uuid'        => $this->warehouse_uuid,
            'bin_location_uuid'     => $this->bin_location_uuid,
            'zone_uuid'             => $this->zone_uuid,
            'supplier_uuid'         => $this->supplier_uuid,
            'supplier'              => $this->whenLoaded('supplier', $this->supplier),
            'product'               => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant'               => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'batch'                 => $this->whenLoaded('batch', new Batch($this->batch)),
            'warehouse'             => $this->whenLoaded('warehouse', new Warehouse($this->warehouse)),
            'zone'                  => $this->whenLoaded('zone', fn () => new WarehouseZone($this->zone)),
            'bin_location'          => $this->whenLoaded('binLocation', fn () => new BinLocation($this->binLocation)),
            'status'                => $this->status,
            'quantity'              => (int) $this->quantity,
            'reserved_quantity'     => (int) $this->reserved_quantity,
            'available_quantity'    => (int) $this->available_quantity,
            'in_transit'            => (int) $this->in_transit,
            'on_order'              => (int) $this->on_order,
            'quarantined'           => (int) $this->quarantined,
            'min_quantity'          => (int) $this->min_quantity,
            'max_quantity'          => (int) $this->max_quantity,
            'reorder_point'         => (int) $this->reorder_point,
            'unit_cost'             => $this->unit_cost,
            'lot_number'            => $this->lot_number,
            'serial_number'         => $this->serial_number,
            'uom'                   => $this->uom,
            'comments'              => $this->comments,
            'expiry_date_at'        => $this->expiry_date_at,
            'manufactured_date_at'  => $this->manufactured_date_at,
            'meta'                  => $this->meta ?? [],
            'received_at'           => $this->received_at,
            'last_counted_at'       => $this->last_counted_at,
            'updated_at'            => $this->updated_at,
            'created_at'            => $this->created_at,
        ];
    }
}
