<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\StockTransfer as StockTransferResource;
use Fleetbase\Pallet\Models\StockTransfer;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;
use RuntimeException;

class StockTransferController extends PalletResourceController
{
    public $resource = 'stock-transfer';

    public function createRecord(Request $request)
    {
        $this->validateRequest($request);

        $data              = $request->input('stock_transfer');
        $fromWarehouseUuid = data_get($data, 'from_warehouse_uuid');
        $toWarehouseUuid   = data_get($data, 'to_warehouse_uuid');
        $fromWarehouse     = $this->findWarehouse($fromWarehouseUuid);
        $toWarehouse       = $this->findWarehouse($toWarehouseUuid);

        if (!$fromWarehouse || !$toWarehouse) {
            return response()->error('Select both source and destination warehouses.', 422);
        }

        if ($fromWarehouse->uuid === $toWarehouse->uuid) {
            return response()->error('Source and destination warehouses must be different.', 422);
        }

        $transfer = new StockTransfer([
            'company_uuid'         => session('company'),
            'from_warehouse_uuid'  => $fromWarehouse->uuid,
            'to_warehouse_uuid'    => $toWarehouse->uuid,
            'type'                 => data_get($data, 'type', 'standard'),
            'status'               => data_get($data, 'status', 'pending'),
            'requested_by_uuid'    => data_get($data, 'requested_by_uuid', session('user')),
            'approved_by_uuid'     => data_get($data, 'approved_by_uuid'),
            'notes'                => data_get($data, 'notes'),
            'meta'                 => data_get($data, 'meta', []),
        ]);

        $transfer->save();

        if (Http::isInternalRequest($request)) {
            StockTransferResource::wrap($this->resourceSingularlName);
        }

        return new StockTransferResource($transfer);
    }

    public function approve(string $id)
    {
        $transfer = $this->findTransfer($id);

        try {
            $transfer->approve(session('user'));
        } catch (RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new StockTransferResource($transfer->fresh());
    }

    public function ship(string $id)
    {
        $transfer = $this->findTransfer($id);

        try {
            $transfer->ship();
        } catch (RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new StockTransferResource($transfer->fresh());
    }

    public function receive(string $id)
    {
        $transfer = $this->findTransfer($id);

        try {
            $transfer->receive();
        } catch (RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new StockTransferResource($transfer->fresh());
    }

    public function cancel(string $id)
    {
        $transfer = $this->findTransfer($id);

        try {
            $transfer->cancel();
        } catch (RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

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

    protected function findWarehouse(?string $id): ?Warehouse
    {
        if (!$id) {
            return null;
        }

        return Warehouse::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
            ->first();
    }
}
