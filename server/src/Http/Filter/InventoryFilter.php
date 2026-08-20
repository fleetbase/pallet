<?php

namespace Fleetbase\Pallet\Http\Filter;

use Fleetbase\Http\Filter\Filter;
use Fleetbase\Pallet\Support\Utils;

class InventoryFilter extends Filter
{
    /**
     * Inventory is tenant-owned, so the listing has to be scoped to the
     * authenticated company. This filter declared neither queryForInternal nor
     * queryForPublic, so it contributed no company clause of its own and the
     * listing returned other companies' rows.
     *
     * The column is qualified because the summarize scope left-joins
     * pallet_batches, which carries a company_uuid of its own.
     */
    private function scopeToCompany(): void
    {
        $this->builder->where('pallet_inventories.company_uuid', $this->session->get('company'));
    }

    public function queryForInternal()
    {
        $this->scopeToCompany();
    }

    public function queryForPublic()
    {
        $this->scopeToCompany();
    }

    /**
     * The Low Stock and Expired Stock screens run over the summarized listing, so
     * they filter on the aggregate aliases from Inventory::scopeSummarizeByProduct.
     *
     * Low stock previously read `total_quantity < minimum_quantity`, which
     * disagreed with the dashboard KPI it is reached from — that counts
     * `min_quantity > 0 AND available_quantity <= min_quantity` and describes
     * itself as "At or below minimum". Anything sitting exactly at its reorder
     * point was counted on the dashboard and then missing from this screen, and
     * reserved stock was ignored entirely. The `minimum_quantity > 0` guard
     * matters once the comparison is inclusive: without it every product with no
     * reorder point set and no stock would report as low.
     */
    public function view(?string $view): void
    {
        if ($view === 'low_stock') {
            $this->builder->havingRaw('minimum_quantity > 0 AND total_available_quantity <= minimum_quantity');
        }

        if ($view === 'expired_stock') {
            // Bound parameter rather than NOW(), which SQLite does not provide.
            $this->builder->havingRaw('latest_expiry_date_at IS NOT NULL AND latest_expiry_date_at <= ?', [now()]);
        }
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
