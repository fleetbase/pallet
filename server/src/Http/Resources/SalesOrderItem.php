<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;

class SalesOrderItem extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                  => $this->public_id,
            'uuid'                => $this->uuid,
            'sales_order_uuid'    => $this->sales_order_uuid,
            'product_uuid'        => $this->product_uuid,
            'warehouse_uuid'      => $this->warehouse_uuid,
            'inventory_uuid'      => $this->inventory_uuid,
            'product'             => $this->whenLoaded('product', fn () => new \Fleetbase\Pallet\Http\Resources\Product($this->product)),
            'warehouse'           => $this->whenLoaded('warehouse', fn () => new \Fleetbase\Pallet\Http\Resources\Warehouse($this->warehouse)),
            'inventory'           => $this->whenLoaded('inventory', fn () => new \Fleetbase\Pallet\Http\Resources\Inventory($this->inventory)),
            'quantity'            => $this->quantity,
            'quantity_fulfilled'  => $this->quantity_fulfilled,
            'outstanding_quantity' => $this->outstanding_quantity,
            'currency'            => $this->currency,
            'unit_price'          => $this->unit_price,
            'total_price'         => $this->total_price,
            'unit_of_measure'     => $this->unit_of_measure,
            'sku'                 => $this->sku,
            'lot_number'          => $this->lot_number,
            'serial_number'       => $this->serial_number,
            'status'              => $this->status,
            'notes'               => $this->notes,
            'meta'                => $this->meta,
            'fulfilled_at'        => $this->fulfilled_at,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
