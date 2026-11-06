<?php

namespace Fleetbase\Pallet\Http\Filter;

/**
 * Tenant scoping for WarehouseZone listings.
 *
 * Before this class existed the model resolved no filter at all, so
 * HasApiModelBehavior::applyCustomFilters() added no company clause and the listing
 * returned every company's rows. Per-column filtering continues to go through the
 * generic filter machinery — only the company scope is declared here.
 */
class WarehouseZoneFilter extends PalletFilter
{
    /**
     * Scope zones to one warehouse.
     *
     * The warehouse detail panel lists that building's zones. Without this the
     * listing returned every zone the company owns across every site.
     */
    public function warehouse(?string $uuid)
    {
        $this->builder->where('warehouse_uuid', $uuid);
    }
}
