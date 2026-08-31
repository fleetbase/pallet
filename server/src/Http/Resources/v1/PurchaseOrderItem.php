<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * A line on a purchase order.
 *
 * `outstanding_quantity` is reported rather than left to the caller to derive,
 * because it is the figure a receipt is capped against.
 *
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\PurchaseOrderItem
 */
class PurchaseOrderItem extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                    => $this->public_id,
            'object'                => 'purchase_order_item',
            'product'               => data_get($this, 'product.public_id'),
            'product_name'          => data_get($this, 'product.name'),
            'variant'               => data_get($this, 'variant.public_id'),
            'warehouse'             => data_get($this, 'warehouse.public_id'),
            'sku'                   => $this->sku,
            'quantity'              => (int) $this->quantity,
            'quantity_received'     => (int) $this->quantity_received,
            'outstanding_quantity'  => (int) $this->outstanding_quantity,
            'currency'              => $this->currency,
            'unit_price'            => $this->unit_price,
            'unit_cost'             => $this->unit_cost,
            'total_price'           => $this->total_price,
            'unit_of_measure'       => $this->unit_of_measure,
            'lot_number'            => $this->lot_number,
            'serial_number'         => $this->serial_number,
            'expiry_date'           => $this->expiry_date,
            'status'                => $this->status,
            'received_at'           => $this->received_at,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
