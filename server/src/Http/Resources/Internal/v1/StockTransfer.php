<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\StockTransfer
 */
class StockTransfer extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'           => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'        => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'from_warehouse_uuid' => $this->from_warehouse_uuid,
            'to_warehouse_uuid'   => $this->to_warehouse_uuid,
            'from_warehouse'      => $this->whenLoaded('fromWarehouse', fn () => new Warehouse($this->fromWarehouse)),
            'to_warehouse'        => $this->whenLoaded('toWarehouse', fn () => new Warehouse($this->toWarehouse)),
            'items'               => $this->whenLoaded('items', fn () => StockTransferItem::collection($this->items)),
            'transfer_number'     => $this->transfer_number,
            'status'              => $this->status,
            'type'                => $this->type,
            'requested_by_uuid'   => $this->requested_by_uuid,
            'approved_by_uuid'    => $this->approved_by_uuid,
            'shipped_at'          => $this->shipped_at,
            'received_at'         => $this->received_at,
            'total_items'         => $this->total_items,
            'total_quantity'      => $this->total_quantity,
            'notes'               => $this->notes,
            'meta'                => $this->meta ?? [],
            'updated_at'          => $this->updated_at,
            'created_at'          => $this->created_at,
        ];
    }
}
