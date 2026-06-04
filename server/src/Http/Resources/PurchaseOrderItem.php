<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;

class PurchaseOrderItem extends FleetbaseResource
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
            'purchase_order_uuid'  => $this->purchase_order_uuid,
            'product_uuid'         => $this->product_uuid,
            'variant_uuid'         => $this->variant_uuid,
            'warehouse_uuid'       => $this->warehouse_uuid,
            'product'              => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant'              => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'warehouse'            => $this->whenLoaded('warehouse', fn () => new Warehouse($this->warehouse)),
            'quantity'             => $this->quantity,
            'quantity_received'    => $this->quantity_received,
            'outstanding_quantity' => $this->outstanding_quantity,
            'currency'             => $this->currency,
            'unit_price'           => $this->unit_price,
            'unit_cost'            => $this->unit_cost,
            'total_price'          => $this->total_price,
            'unit_of_measure'      => $this->unit_of_measure,
            'sku'                  => $this->sku,
            'lot_number'           => $this->lot_number,
            'serial_number'        => $this->serial_number,
            'expiry_date'          => $this->expiry_date,
            'status'               => $this->status,
            'notes'                => $this->notes,
            'meta'                 => $this->meta,
            'received_at'          => $this->received_at,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
