<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

class SalesOrder extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                      => $this->public_id,
            'object'                  => 'sales_order',
            'status'                  => $this->status,
            'customer'                => data_get($this, 'customer.public_id'),
            'customer_name'           => data_get($this, 'customer.name'),
            'customer_reference_code' => $this->customer_reference_code,
            'supplier'                => data_get($this, 'supplier.public_id'),
            'warehouse'               => data_get($this, 'warehouse.public_id'),
            'warehouse_name'          => data_get($this, 'warehouse.name'),
            'reference_code'          => $this->reference_code,
            'reference_url'           => $this->reference_url,
            'description'             => $this->description,
            'comments'                => $this->comments,
            'total_value'             => $this->total_value,
            'order_date_at'           => $this->order_date_at,
            'expected_delivery_at'    => $this->expected_delivery_at,
            'items'                   => $this->whenLoaded('items', fn () => SalesOrderItem::collection($this->items)),
            'meta'                    => $this->meta,
            'created_at'              => $this->created_at,
            'updated_at'              => $this->updated_at,
        ];
    }

    public function toWebhookPayload()
    {
        return $this->toArray(request());
    }
}
