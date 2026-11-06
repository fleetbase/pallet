<?php

namespace Fleetbase\Pallet\Http\Filter;

class WarehouseFilter extends PalletFilter
{
    public function query(?string $query)
    {
        $this->builder->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('code', 'like', "%{$query}%")
              ->orWhere('type', 'like', "%{$query}%");
        });
    }

    public function type(?string $type)
    {
        if ($type) {
            $this->builder->where('type', $type);
        }
    }

    public function status(?string $status)
    {
        if ($status) {
            $this->builder->where('status', $status);
        }
    }

    public function isActive(?bool $isActive)
    {
        if ($isActive !== null) {
            $this->builder->where('is_active', $isActive);
        }
    }
}
