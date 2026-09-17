<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * Consumable representation of a purchase order.
 *
 * Lines are included whenever they have been loaded — an order without its lines
 * tells a consumer almost nothing, and receiving requires their ids.
 *
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\PurchaseOrder
 */
class PurchaseOrder extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                   => $this->public_id,
            'object'               => 'purchase_order',
            'status'               => $this->status,
            'supplier'             => data_get($this, 'supplier.public_id'),
            'supplier_name'        => data_get($this, 'supplier.name'),
            'warehouse'            => data_get($this, 'warehouse.public_id'),
            'warehouse_name'       => data_get($this, 'warehouse.name'),
            'reference_code'       => $this->reference_code,
            'reference_url'        => $this->reference_url,
            'description'          => $this->description,
            'comments'             => $this->comments,
            'currency'             => $this->currency,
            'total_value'          => $this->total_value,
            'order_created_at'     => $this->order_created_at,
            'expected_delivery_at' => $this->expected_delivery_at,
            'items'                => $this->whenLoaded('items', fn () => PurchaseOrderItem::collection($this->items)),
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
