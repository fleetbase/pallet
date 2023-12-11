<?php

namespace Fleetbase\Pallet\Http\Filter;

use Fleetbase\Http\Filter\Filter;
use Fleetbase\Pallet\Support\Utils;
use Illuminate\Support\Facades\DB;


class InventoryFilter extends Filter
{
    public function view(?string $view): void
    {
        if ($view === 'low_stock') {
            $this->builder->havingRaw('total_quantity < minimum_quantity');
        }

        if ($view === 'expired_stock') {
            $this->builder->havingRaw('latest_expiry_date_at <= NOW()');
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
