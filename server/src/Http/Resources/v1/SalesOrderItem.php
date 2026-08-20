<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * A line on a sales order.
 *
 * The model has no outstanding-quantity accessor of its own, so it is derived here
 * rather than left for the caller — it is the figure that decides whether the order
 * is partial or complete.
 */
class SalesOrderItem extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                   => $this->public_id,
            'object'               => 'sales_order_item',
            'product'              => data_get($this, 'product.public_id'),
            'product_name'         => data_get($this, 'product.name'),
            'variant'              => data_get($this, 'variant.public_id'),
            'warehouse'            => data_get($this, 'warehouse.public_id'),
            'sku'                  => $this->sku,
            'quantity'             => (int) $this->quantity,
            'quantity_fulfilled'   => (int) $this->quantity_fulfilled,
            'outstanding_quantity' => max(0, (int) $this->quantity - (int) $this->quantity_fulfilled),
            'currency'             => $this->currency,
            'unit_price'           => $this->unit_price,
            'total_price'          => $this->total_price,
            'unit_of_measure'      => $this->unit_of_measure,
            'lot_number'           => $this->lot_number,
            'serial_number'        => $this->serial_number,
            'status'               => $this->status,
            'notes'                => $this->notes,
            'fulfilled_at'         => $this->fulfilled_at,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
