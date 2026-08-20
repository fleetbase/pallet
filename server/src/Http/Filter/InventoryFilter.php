<?php

namespace Fleetbase\Pallet\Http\Filter;

use Fleetbase\Pallet\Support\Utils;

class InventoryFilter extends PalletFilter
{
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
