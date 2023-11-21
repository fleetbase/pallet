<?php

namespace Fleetbase\Pallet\Http\Resources;

use Fleetbase\Http\Resources\FleetbaseResource;

class IndexInventory extends FleetbaseResource
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
            'uuid' => $this->latest_uuid,
            'public_id' => $this->latest_public_id,
            'product_uuid' => $this->product_uuid,
            'product' => $this->whenLoaded('product', $this->product),
            'quantity' => (int) $this->total_quantity,
            'comments' => $this->latest_comments,
            'updated_at' => $this->latest_updated_at,
            'created_at' => $this->latest_created_at
        ];
    }
}
