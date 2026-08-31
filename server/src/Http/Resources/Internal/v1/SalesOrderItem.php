<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\SalesOrderItem
 */
class SalesOrderItem extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                   => $this->public_id,
            'uuid'                 => $this->uuid,
            'public_id'            => $this->public_id,
            'sales_order_uuid'     => $this->sales_order_uuid,
            'product_uuid'         => $this->product_uuid,
            'variant_uuid'         => $this->variant_uuid,
            'warehouse_uuid'       => $this->warehouse_uuid,
            'inventory_uuid'       => $this->inventory_uuid,
            'product'              => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant'              => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'warehouse'            => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),
            'inventory'            => $this->whenLoaded('inventory', fn () => new Inventory($this->inventory)),
            'quantity'             => $this->quantity,
            'quantity_fulfilled'   => $this->quantity_fulfilled,
            'outstanding_quantity' => $this->outstanding_quantity,
            'currency'             => $this->currency,
            'unit_price'           => $this->unit_price,
            'total_price'          => $this->total_price,
            'unit_of_measure'      => $this->unit_of_measure,
            'sku'                  => $this->sku,
            'lot_number'           => $this->lot_number,
            'serial_number'        => $this->serial_number,
            'status'               => $this->status,
            'notes'                => $this->notes,
            'meta'                 => $this->meta,
            'fulfilled_at'         => $this->fulfilled_at,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
