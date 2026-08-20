<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * A received lot. `quantity` is the quantity the batch arrived with, not the
 * quantity still on hand — that lives on the stock record the batch is attached to.
 */
class Batch extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->public_id,
            'object'              => 'batch',
            'batch_number'        => $this->batch_number,
            'product'             => data_get($this, 'product.public_id'),
            'product_name'        => data_get($this, 'product.name'),
            'variant'             => data_get($this, 'variant.public_id'),
            'received_quantity'   => (int) $this->quantity,
            'manufacture_date_at' => $this->manufacture_date_at,
            'expiry_date_at'      => $this->expiry_date_at,
            'meta'                => $this->meta,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
