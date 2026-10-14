<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

/**
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\PurchaseOrder
 */
class PurchaseOrder extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'                           => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                         => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'                    => $this->when(Http::isInternalRequest(), $this->public_id),
            'order_number'                 => $this->order_number,
            'supplier_uuid'                => $this->supplier_uuid,
            'supplier'                     => $this->whenLoaded('supplier', $this->supplier),
            'warehouse_uuid'               => $this->warehouse_uuid,
            'transaction_uuid'             => $this->transaction_uuid,
            'warehouse'                    => $this->whenLoaded('warehouse', $this->warehouse),
            'assigned_to_uuid'             => $this->assigned_to_uuid,
            'assigned_to'                  => $this->whenLoaded('assignedTo', $this->assignedTo),
            'point_of_contact_uuid'        => $this->point_of_contact_uuid,
            'point_of_contact'             => $this->whenLoaded('pointOfContact', $this->pointOfContact),
            'status'                       => $this->status,
            'comments'                     => $this->comments,
            'description'                  => $this->description,
            'reference_code'               => $this->reference_code,
            'reference_url'                => $this->reference_url,
            'currency'                     => $this->currency,
            'meta'                         => $this->meta ?? [],
            'order_date_at'                => $this->order_created_at,
            'expected_delivery_at'         => $this->expected_delivery_at,
            // Line items — always included so the frontend can render the items tab
            'items'                     => PurchaseOrderItem::collection($this->whenLoaded('items', $this->items ?? [])),
            'item_count'                => $this->item_count,
            'total_value'               => $this->total_value,
            'updated_at'                => $this->updated_at,
            'created_at'                => $this->created_at,
        ];
    }
}
