<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

class ProductVariant extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->public_id,
            'object'         => 'product_variant',
            'product'        => data_get($this, 'product.public_id'),
            'product_name'   => data_get($this, 'product.name'),
            'name'           => $this->name,
            'sku'            => $this->sku,
            'barcode'        => $this->barcode,
            'option_values'  => $this->option_values,
            'currency'       => $this->currency,
            'unit_cost'      => $this->unit_cost,
            'unit_price'     => $this->unit_price,
            'sale_price'     => $this->sale_price,
            'declared_value' => $this->declared_value,
            'weight'         => $this->weight,
            'weight_unit'    => $this->weight_unit,
            'status'         => $this->status,
            'meta'           => $this->meta,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }

    public function toWebhookPayload()
    {
        return $this->toArray(request());
    }
}
