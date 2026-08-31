<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\Inventory
 *
 * latest_uuid and latest_public_id are aliases produced by the inventory summary
 * query (summarizeByProduct), not columns on the model — the listing groups rows by
 * product and carries the newest row's identity through the aggregate.
 *
 * @property ?string $latest_uuid
 * @property ?string $latest_public_id
 */
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
            'status'             => $this->summary_status ?? $this->status,
            // Every field here reads an alias produced by Inventory::scopeSummarizeByProduct.
            // The same controller also serves the unsummarised listing (`by_warehouse=1`),
            // where none of those aliases exist — so each falls back to the real column it
            // aggregates. Without the fallback a per-warehouse row reported every quantity
            // as zero, which looks like an answer rather than a missing one.
            'quantity'           => (int) ($this->total_quantity ?? $this->quantity),
            'reserved_quantity'  => (int) ($this->total_reserved_quantity ?? $this->reserved_quantity),
            'available_quantity' => (int) ($this->total_available_quantity ?? $this->available_quantity),
            'in_transit'         => (int) ($this->total_in_transit ?? $this->in_transit),
            'on_order'           => (int) ($this->total_on_order ?? $this->on_order),
            'quarantined'        => (int) ($this->total_quarantined ?? $this->quarantined),
            'min_quantity'       => (int) ($this->minimum_quantity ?? $this->min_quantity),
            'comments'           => $this->latest_comments ?? $this->comments,
            'expiry_date_at'     => $this->latest_expiry_date_at ?? $this->expiry_date_at,
            'updated_at'         => $this->latest_updated_at ?? $this->updated_at,
            'created_at'         => $this->latest_created_at ?? $this->created_at,
        ];
    }
}
