<?php

namespace Fleetbase\Pallet\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * Consumable representation of a product.
 *
 * Deliberately narrower than the internal resource: a consumer is keyed on
 * `public_id`, so the internal uuids (company, creator, photo, storefront link) and
 * the console's derived inventory_summary block are not part of the contract.
 * Related records are referenced by their own public id, never by uuid.
 *
 * A JsonResource forwards every unknown property to the model it wraps, through
 * __get. Naming that model here is what lets static analysis follow the forward;
 * without it every $this->column read is an undefined property.
 *
 * @mixin \Fleetbase\Pallet\Models\Product
 */
class Product extends FleetbaseResource
{
    public function toArray($request)
    {
        return [
            'id'                     => $this->public_id,
            'object'                 => 'product',
            'internal_id'            => $this->internal_id,
            'name'                   => $this->name,
            'description'            => $this->description,
            'sku'                    => $this->sku,
            'barcode'                => $this->barcode,
            'status'                 => $this->status,
            'slug'                   => $this->slug,
            'currency'               => $this->currency,
            'unit_cost'              => $this->unit_cost,
            'unit_price'             => $this->unit_price,
            'sale_price'             => $this->sale_price,
            'declared_value'         => $this->declared_value,
            'weight'                 => $this->weight,
            'weight_unit'            => $this->weight_unit,
            'length'                 => $this->length,
            'width'                  => $this->width,
            'height'                 => $this->height,
            'dimensions_unit'        => $this->dimensions_unit,
            'has_variants'           => (bool) $this->has_variants,
            'variant_count'          => (int) $this->variant_count,
            'is_serialized'          => (bool) $this->is_serialized,
            'is_lot_tracked'         => (bool) $this->is_lot_tracked,
            'is_kit'                 => (bool) $this->is_kit,
            'is_perishable'          => (bool) $this->is_perishable,
            'requires_quality_check' => (bool) $this->requires_quality_check,
            'reorder_point'          => (int) $this->reorder_point,
            'reorder_quantity'       => (int) $this->reorder_quantity,
            'shelf_life_days'        => (int) $this->shelf_life_days,
            'total_stock'            => (int) $this->total_stock,
            'available_stock'        => (int) $this->available_stock,
            'reserved_stock'         => (int) $this->reserved_stock,
            'is_out_of_stock'        => (bool) $this->is_out_of_stock,
            'photo_url'              => $this->photo_url,
            'supplier'               => data_get($this, 'supplier.public_id'),
            'category'               => data_get($this, 'category.public_id'),
            'meta'                   => $this->meta,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }

    public function toWebhookPayload()
    {
        return $this->toArray(request());
    }
}
