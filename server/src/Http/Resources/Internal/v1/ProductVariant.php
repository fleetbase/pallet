<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class ProductVariant extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                      => $this->when(Http::isInternalRequest(), $this->public_id, $this->public_id),
            'uuid'                    => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'               => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'            => $this->company_uuid,
            'created_by_uuid'         => $this->created_by_uuid,
            'product_uuid'            => $this->product_uuid,
            'storefront_variant_uuid' => $this->storefront_variant_uuid,
            'product'                 => $this->whenLoaded('product', fn () => new Product($this->product)),
            'name'                    => $this->name,
            'display_name'            => $this->display_name,
            'sku'                     => $this->sku,
            'barcode'                 => $this->barcode,
            'option_values'           => $this->option_values,
            'currency'                => $this->currency,
            'unit_cost'               => $this->unit_cost,
            'unit_price'              => $this->unit_price,
            'sale_price'              => $this->sale_price,
            'declared_value'          => $this->declared_value,
            'weight'                  => $this->weight,
            'weight_unit'             => $this->weight_unit,
            'total_stock'             => (int) $this->total_stock,
            'available_stock'         => (int) $this->available_stock,
            'reserved_stock'          => (int) $this->reserved_stock,
            'is_out_of_stock'         => (bool) $this->is_out_of_stock,
            'inventory_summary'       => [
                'product_uuid'             => $this->product_uuid,
                'variant_uuid'             => $this->uuid,
                'storefront_product_uuid'  => $this->product?->storefront_product_uuid,
                'storefront_variant_uuid'  => $this->storefront_variant_uuid,
                'total_quantity'           => (int) $this->total_stock,
                'available_quantity'       => (int) $this->available_stock,
                'reserved_quantity'        => (int) $this->reserved_stock,
                'out_of_stock'             => (bool) $this->is_out_of_stock,
                'low_stock'                => $this->product?->reorder_point !== null && (int) $this->available_stock <= (int) $this->product->reorder_point,
                'reorder_point'            => (int) ($this->product?->reorder_point ?? 0),
                'reorder_quantity'         => (int) ($this->product?->reorder_quantity ?? 0),
            ],
            'status'          => $this->status,
            'meta'            => $this->meta,
            'updated_at'      => $this->updated_at,
            'created_at'      => $this->created_at,
        ];
    }
}
