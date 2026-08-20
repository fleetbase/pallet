<?php

namespace Fleetbase\Pallet\Http\Filter;

use Fleetbase\Http\Filter\Filter;

class StockTransactionFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where('company_uuid', $this->session->get('company'));
    }

    public function queryForPublic()
    {
        $this->builder->where('company_uuid', $this->session->get('company'));
    }

    public function query(?string $query)
    {
        $this->builder->search($query);
    }

    public function transactionType(?string $transactionType)
    {
        $this->builder->searchWhere('transaction_type', $transactionType);
    }

    public function inventory(?string $inventoryUuid)
    {
        $this->builder->where('inventory_uuid', $inventoryUuid);
    }

    public function product(?string $productUuid)
    {
        $this->builder->where('product_uuid', $productUuid);
    }

    public function batch(?string $batchUuid)
    {
        $this->builder->where('batch_uuid', $batchUuid);
    }

    public function warehouse(?string $warehouseUuid)
    {
        $this->builder->where('destination_uuid', $warehouseUuid);
    }
}
