<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\StockTransfer as StockTransferResource;
use Fleetbase\Pallet\Models\StockTransfer;

class StockTransferController extends PalletResourceController
{
    public $resource = 'stock-transfer';

    public function approve(string $id)
    {
        $transfer = $this->findTransfer($id);
        $transfer->approve(session('user'));

        return new StockTransferResource($transfer->fresh());
    }

    public function ship(string $id)
    {
        $transfer = $this->findTransfer($id);
        $transfer->ship();

        return new StockTransferResource($transfer->fresh());
    }

    public function receive(string $id)
    {
        $transfer = $this->findTransfer($id);
        $transfer->receive();

        return new StockTransferResource($transfer->fresh());
    }

    public function cancel(string $id)
    {
        $transfer = $this->findTransfer($id);
        $transfer->cancel();

        return new StockTransferResource($transfer->fresh());
    }

    protected function findTransfer(string $id): StockTransfer
    {
        return StockTransfer::where('company_uuid', session('company'))
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();
    }
}
