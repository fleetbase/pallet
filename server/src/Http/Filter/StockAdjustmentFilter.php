<?php

namespace Fleetbase\Pallet\Http\Filter;

/**
 * Tenant scoping for StockAdjustment listings.
 *
 * Before this class existed the model resolved no filter at all, so
 * HasApiModelBehavior::applyCustomFilters() added no company clause and the listing
 * returned every company's rows. Per-column filtering continues to go through the
 * generic filter machinery — only the company scope is declared here.
 */
class StockAdjustmentFilter extends PalletFilter
{
    /**
     * Scope adjustments to one inventory record.
     *
     * The inventory detail panel needs to show the adjustments made against the record
     * it is displaying. Without this the only way to filter was client-side over every
     * adjustment the company has ever made.
     */
    public function inventory(?string $inventoryUuid)
    {
        $this->builder->where('inventory_uuid', $inventoryUuid);
    }
}
