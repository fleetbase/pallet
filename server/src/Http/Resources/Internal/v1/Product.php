<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class Product extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                      => $this->when(Http::isInternalRequest(), $this->public_id, $this->public_id),
            'uuid'                    => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'               => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'            => $this->company_uuid,
            'created_by_uuid'         => $this->created_by_uuid,
            'category_uuid'           => $this->category_uuid,
            'supplier_uuid'           => $this->supplier_uuid,
            'storefront_product_uuid' => $this->storefront_product_uuid,
            'photo_uuid'              => $this->photo_uuid,
            'photo_url'               => $this->photo_url,
            'supplier'                => $this->whenLoaded('supplier', $this->supplier),
            'category'                => $this->whenLoaded('category', $this->category),
            'variants'                => $this->whenLoaded('variants', fn () => ProductVariant::collection($this->variants)),
            'files'                   => $this->whenLoaded('files', $this->files),
            'internal_id'             => $this->internal_id,
            'name'                    => $this->name,
            'description'             => $this->description,
            'sku'                     => $this->sku,
            'barcode'                 => $this->barcode,
            'currency'                => $this->currency,
            'unit_cost'               => $this->unit_cost,
            'unit_price'              => $this->unit_price,
            'sale_price'              => $this->sale_price,
            'declared_value'          => $this->declared_value,
            'weight'                  => $this->weight,
            'weight_unit'             => $this->weight_unit,
            'length'                  => $this->length,
            'width'                   => $this->width,
            'height'                  => $this->height,
            'dimensions_unit'         => $this->dimensions_unit,
            'dimensions'              => $this->dimensions,
            'has_variants'            => (bool) $this->has_variants,
            'variant_count'           => (int) $this->variant_count,
            'is_serialized'           => (bool) $this->is_serialized,
            'is_lot_tracked'          => (bool) $this->is_lot_tracked,
            'is_kit'                  => (bool) $this->is_kit,
            'is_perishable'           => (bool) $this->is_perishable,
            'requires_quality_check'  => (bool) $this->requires_quality_check,
            'reorder_point'           => (int) $this->reorder_point,
            'reorder_quantity'        => (int) $this->reorder_quantity,
            'shelf_life_days'         => (int) $this->shelf_life_days,
            'total_stock'             => (int) $this->total_stock,
            'available_stock'         => (int) $this->available_stock,
            'reserved_stock'          => (int) $this->reserved_stock,
            'is_out_of_stock'         => (bool) $this->is_out_of_stock,
            'inventory_summary'       => [
                'product_uuid'             => $this->uuid,
                'variant_uuid'             => null,
                'storefront_product_uuid'  => $this->storefront_product_uuid,
                'storefront_variant_uuid'  => null,
                'total_quantity'           => (int) $this->total_stock,
                'available_quantity'       => (int) $this->available_stock,
                'reserved_quantity'        => (int) $this->reserved_stock,
                'out_of_stock'             => (bool) $this->is_out_of_stock,
                'low_stock'                => $this->reorder_point !== null && (int) $this->available_stock <= (int) $this->reorder_point,
                'reorder_point'            => (int) $this->reorder_point,
                'reorder_quantity'         => (int) $this->reorder_quantity,
            ],
            'status'                 => $this->status,
            'slug'                   => $this->slug,
            'meta'                   => $this->meta,
            'updated_at'             => $this->updated_at,
            'created_at'             => $this->created_at,
        ];
    }
}
