<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;

class IndexInventory extends FleetbaseResource
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
            'uuid'               => $this->latest_uuid,
            'public_id'          => $this->latest_public_id,
            'product_uuid'       => $this->product_uuid,
            'variant_uuid'       => $this->variant_uuid,
            'product'            => $this->whenLoaded('product', $this->product),
            'variant'            => $this->whenLoaded('variant', $this->variant),
            'batch'              => $this->whenLoaded('batch', new Batch($this->batch)),
            'batch_uuid'         => $this->batch_uuid,
            'supplier_uuid'      => $this->supplier_uuid,
            'supplier'           => $this->whenLoaded('supplier', $this->supplier),
            'warehouse_uuid'     => $this->warehouse_uuid,
            'warehouse'          => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),
            'status'             => $this->summary_status,
            'quantity'           => (int) $this->total_quantity,
            'reserved_quantity'  => (int) $this->total_reserved_quantity,
            'available_quantity' => (int) $this->total_available_quantity,
            'min_quantity'       => (int) $this->minimum_quantity,
            'comments'           => $this->latest_comments,
            'expiry_date_at'     => $this->latest_expiry_date_at,
            'updated_at'         => $this->latest_updated_at,
            'created_at'         => $this->latest_created_at,
        ];
    }
}
