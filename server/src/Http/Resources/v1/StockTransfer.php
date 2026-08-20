<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

class StockTransfer extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->public_id,
            'object'              => 'stock_transfer',
            'transfer_number'     => $this->transfer_number,
            'status'              => $this->status,
            'type'                => $this->type,
            'from_warehouse'      => data_get($this, 'fromWarehouse.public_id'),
            'from_warehouse_name' => data_get($this, 'fromWarehouse.name'),
            'to_warehouse'        => data_get($this, 'toWarehouse.public_id'),
            'to_warehouse_name'   => data_get($this, 'toWarehouse.name'),
            'notes'               => $this->notes,
            'shipped_at'          => $this->shipped_at,
            'received_at'         => $this->received_at,
            'items'               => $this->whenLoaded('items', fn () => StockTransferItem::collection($this->items)),
            'meta'                => $this->meta,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }

    public function toWebhookPayload()
    {
        return $this->toArray(request());
    }
}
