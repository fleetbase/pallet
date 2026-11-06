<?php

namespace Fleetbase\Pallet\Http\Filter;

use Fleetbase\Pallet\Support\Utils;

class ProductFilter extends PalletFilter
{
    public function query(?string $query)
    {
        $this->builder->search($query);
    }

    public function name(?string $name)
    {
        $this->builder->searchWhere('name', $name);
    }

    public function internalId(?string $internalId)
    {
        $this->builder->searchWhere('internal_id', $internalId);
    }

    public function publicId(?string $publicId)
    {
        $this->builder->searchWhere('public_id', $publicId);
    }

    public function sku(?string $sku)
    {
        $this->builder->searchWhere('sku', $sku);
    }

    public function price(?string $price)
    {
        $this->builder->searchWhere('unit_price', $price);
    }

    public function salePrice(?string $salePrice)
    {
        $this->builder->searchWhere('sale_price', $salePrice);
    }

    public function declaredValue(?string $declaredValue)
    {
        $this->builder->searchWhere('declared_value', $declaredValue);
    }

    public function length(?string $length)
    {
        $this->builder->searchWhere('length', $length);
    }

    public function width(?string $width)
    {
        $this->builder->searchWhere('width', $width);
    }

    public function height(?string $height)
    {
        $this->builder->searchWhere('height', $height);
    }

    public function weight(?string $weight)
    {
        $this->builder->searchWhere('weight', $weight);
    }

    /**
     * Scope products to one supplier.
     *
     * `supplier` was already declared in the model's $filterParams, but no method
     * existed to act on it, so the parameter was accepted and silently ignored — the
     * listing came back unfiltered and looked like the supplier stocked everything.
     */
    public function supplier(?string $supplierUuid)
    {
        $this->builder->where('supplier_uuid', $supplierUuid);
    }

    public function category(?string $categoryUuid)
    {
        $this->builder->where('category_uuid', $categoryUuid);
    }

    public function createdAt($createdAt)
    {
        $createdAt = Utils::dateRange($createdAt);

        if (is_array($createdAt)) {
            $this->builder->whereBetween('created_at', $createdAt);
        } else {
            $this->builder->whereDate('created_at', $createdAt);
        }
    }

    public function updatedAt($updatedAt)
    {
        $updatedAt = Utils::dateRange($updatedAt);

        if (is_array($updatedAt)) {
            $this->builder->whereBetween('updated_at', $updatedAt);
        } else {
            $this->builder->whereDate('updated_at', $updatedAt);
        }
    }
}
