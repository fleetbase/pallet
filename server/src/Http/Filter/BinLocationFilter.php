<?php

namespace Fleetbase\Pallet\Http\Filter;

/**
 * Tenant scoping for BinLocation listings.
 *
 * Before this class existed the model resolved no filter at all, so
 * HasApiModelBehavior::applyCustomFilters() added no company clause and the listing
 * returned every company's rows. Per-column filtering continues to go through the
 * generic filter machinery — only the company scope is declared here.
 */
class BinLocationFilter extends PalletFilter
{
    /**
     * Scope bins to one warehouse.
     *
     * Bins belong to a building before they belong to anything else.
     */
    public function warehouse(?string $uuid)
    {
        $this->builder->where('warehouse_uuid', $uuid);
    }

    /**
     * Scope bins to one zone.
     *
     * Drilling from a zone to its bins. SCREENS.md section C's must-never for
     * facilities is showing a warehouse's bins as a flat 4,000-row list, so bins are
     * always reached through their zone rather than listed whole.
     */
    public function zone(?string $uuid)
    {
        $this->builder->where('zone_uuid', $uuid);
    }
}
