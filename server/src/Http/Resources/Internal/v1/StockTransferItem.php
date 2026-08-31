<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

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
            'id'                  => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'           => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'        => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'stock_transfer_uuid' => $this->stock_transfer_uuid,
            'product_uuid'        => $this->product_uuid,
            'variant_uuid'        => $this->variant_uuid,
            'product'             => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant'             => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'quantity'            => (int) $this->quantity,
            'quantity_received'   => (int) $this->quantity_received,
            'lot_number'          => $this->lot_number,
            'serial_number'       => $this->serial_number,
            'notes'               => $this->notes,
            'meta'                => $this->meta ?? [],
            'updated_at'          => $this->updated_at,
            'created_at'          => $this->created_at,
        ];
    }
}
