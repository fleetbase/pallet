<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * Consumable representation of a stock record.
 *
 * `available_quantity` is the figure an integrator should act on — on-hand minus
 * what is already reserved — so it is reported alongside both of its inputs rather
 * than in place of them.
 *
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\Inventory
 */
class Inventory extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                   => $this->public_id,
            'object'               => 'inventory',
            'product'              => data_get($this, 'product.public_id'),
            'product_name'         => data_get($this, 'product.name'),
            'sku'                  => data_get($this, 'product.sku'),
            'variant'              => data_get($this, 'variant.public_id'),
            'warehouse'            => data_get($this, 'warehouse.public_id'),
            'warehouse_name'       => data_get($this, 'warehouse.name'),
            'batch'                => data_get($this, 'batch.public_id'),
            'bin_location'         => data_get($this, 'binLocation.public_id'),
            'zone'                 => data_get($this, 'zone.public_id'),
            'quantity'             => (int) $this->quantity,
            'reserved_quantity'    => (int) $this->reserved_quantity,
            'available_quantity'   => (int) $this->available_quantity,
            'min_quantity'         => (int) $this->min_quantity,
            'max_quantity'         => (int) $this->max_quantity,
            'reorder_point'        => (int) $this->reorder_point,
            'is_low_stock'         => (bool) $this->is_low_stock,
            'is_out_of_stock'      => (bool) $this->is_out_of_stock,
            'uom'                  => $this->uom,
            'unit_cost'            => $this->unit_cost,
            'lot_number'           => $this->lot_number,
            'serial_number'        => $this->serial_number,
            'status'               => $this->status,
            'manufactured_date_at' => $this->manufactured_date_at,
            'expiry_date_at'       => $this->expiry_date_at,
            'received_at'          => $this->received_at,
            'last_counted_at'      => $this->last_counted_at,
            'meta'                 => $this->meta,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }

    public function toWebhookPayload()
    {
        return $this->toArray(request());
    }
}
