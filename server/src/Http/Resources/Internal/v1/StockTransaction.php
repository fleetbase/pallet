<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class StockTransaction extends FleetbaseResource
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
            'id'                     => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                   => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'              => $this->when(Http::isInternalRequest(), $this->public_id),

            // What moved, and by how much
            'transaction_type'       => $this->transaction_type,
            'quantity'               => (int) $this->quantity,
            'balance_after'          => $this->balance_after === null ? null : (int) $this->balance_after,

            // What it moved against
            'product_uuid'           => $this->product_uuid,
            'product'                => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant_uuid'           => $this->variant_uuid,
            'variant'                => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'batch_uuid'             => $this->batch_uuid,
            'batch'                  => $this->whenLoaded('batch', fn () => new Batch($this->batch)),
            'inventory_uuid'         => $this->inventory_uuid,
            'inventory'              => $this->whenLoaded('inventory', fn () => new Inventory($this->inventory)),

            // Where it came from and went to
            'source_uuid'            => $this->source_uuid,
            'source_type'            => $this->source_type,
            'destination_uuid'       => $this->destination_uuid,
            'warehouse'              => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),

            // Who recorded it
            'created_by_uuid'        => $this->when(Http::isInternalRequest(), $this->created_by_uuid),
            'created_by'             => $this->whenLoaded('createdBy', fn () => $this->createdBy ? [
                'uuid' => $this->createdBy->uuid,
                'name' => $this->createdBy->name,
            ] : null),

            'meta'                   => $this->meta ?? [],
            'transaction_date_at'    => $this->transaction_date_at,
            'transaction_created_at' => $this->transaction_created_at,
            'updated_at'             => $this->updated_at,
            'created_at'             => $this->created_at,
        ];
    }
}
