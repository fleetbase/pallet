<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\StockTransferItem
 */
class StockTransferItem extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->public_id,
            'object'            => 'stock_transfer_item',
            'product'           => data_get($this, 'product.public_id'),
            'product_name'      => data_get($this, 'product.name'),
            'variant'           => data_get($this, 'variant.public_id'),
            'quantity'          => (int) $this->quantity,
            'quantity_received' => (int) $this->quantity_received,
            'lot_number'        => $this->lot_number,
            'serial_number'     => $this->serial_number,
            'notes'             => $this->notes,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
