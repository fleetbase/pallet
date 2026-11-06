<?php

namespace Fleetbase\Pallet\Http\Filter;

/**
 * Tenant scoping for PickListItem listings.
 *
 * Before this class existed the model resolved no filter at all, so
 * HasApiModelBehavior::applyCustomFilters() added no company clause and the listing
 * returned every company's rows. Per-column filtering continues to go through the
 * generic filter machinery — only the company scope is declared here.
 */
class PickListItemFilter extends PalletFilter
{
}
