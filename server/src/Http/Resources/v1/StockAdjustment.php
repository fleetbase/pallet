<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * Consumable representation of a stock adjustment.
 *
 * Before and after are both reported, and the signed delta with them, so a caller
 * reconciling against their own records does not have to infer the direction from
 * the adjustment type.
 */
class StockAdjustment extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->public_id,
            'object'            => 'stock_adjustment',
            'type'              => $this->type,
            'reason'            => $this->reason,
            'product'           => data_get($this, 'product.public_id'),
            'product_name'      => data_get($this, 'product.name'),
            'variant'           => data_get($this, 'variant.public_id'),
            'warehouse'         => data_get($this, 'warehouse.public_id'),
            'warehouse_name'    => data_get($this, 'warehouse.name'),
            'inventory'         => data_get($this, 'inventory.public_id'),
            'quantity'          => (int) $this->quantity,
            'before_quantity'   => (int) $this->before_quantity,
            'after_quantity'    => (int) $this->after_quantity,
            'delta'             => (int) $this->after_quantity - (int) $this->before_quantity,
            'approval_required' => (bool) $this->approval_required,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }

    public function toWebhookPayload()
    {
        return $this->toArray(request());
    }
}
