<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\Internal\v1\PickList as PickListResource;
use Fleetbase\Pallet\Models\PickList;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\Wave;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;

class PickListController extends PalletResourceController
{
    public $resource = 'pick-list';

    public function createRecord(Request $request)
    {
        $this->validateRequest($request);

        $data      = $request->input('pick_list');
        $warehouse = $this->findWarehouse(data_get($data, 'warehouse_uuid'));

        if (!$warehouse) {
            return response()->error('Select a warehouse for this pick list.', 422);
        }

        $waveId = data_get($data, 'wave_uuid');
        $wave   = $waveId ? $this->findWave($waveId) : null;

        if ($waveId && !$wave) {
            return response()->error('Selected wave could not be found.', 422);
        }

        $salesOrderId = data_get($data, 'sales_order_uuid');
        $salesOrder   = $salesOrderId ? $this->findSalesOrder($salesOrderId) : null;

        if ($salesOrderId && !$salesOrder) {
            return response()->error('Selected sales order could not be found.', 422);
        }

        $pickList = new PickList([
            'company_uuid'      => session('company'),
            'warehouse_uuid'    => $warehouse->uuid,
            'sales_order_uuid'  => $salesOrder?->uuid,
            'wave_uuid'         => $wave?->uuid,
            'assigned_to_uuid'  => data_get($data, 'assigned_to_uuid'),
            'type'              => data_get($data, 'type', 'discrete'),
            'priority'          => data_get($data, 'priority', 5),
            'status'            => data_get($data, 'status', 'pending'),
            'notes'             => data_get($data, 'notes'),
            'meta'              => data_get($data, 'meta', []),
        ]);

        $pickList->save();

        if (Http::isInternalRequest($request)) {
            PickListResource::wrap($this->resourceSingularlName);
        }

        return new PickListResource($pickList);
    }

    public function start(string $id)
    {
        $pickList = $this->findPickList($id);

        try {
            $pickList->start();
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new PickListResource($pickList->fresh(['warehouse', 'salesOrder', 'wave', 'assignedTo', 'items']));
    }

    public function complete(string $id)
    {
        $pickList = $this->findPickList($id);

        try {
            $pickList->complete();
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new PickListResource($pickList->fresh(['warehouse', 'salesOrder', 'wave', 'assignedTo', 'items']));
    }

    public function assign(Request $request, string $id)
    {
        $pickList = $this->findPickList($id);

        try {
            $pickList->assignTo($request->input('assigned_to_uuid'));
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new PickListResource($pickList->fresh(['warehouse', 'salesOrder', 'wave', 'assignedTo', 'items']));
    }

    protected function findPickList(string $id): PickList
    {
        return PickList::where('company_uuid', session('company'))
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

    protected function findWave(string $id): ?Wave
    {
        return Wave::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
            ->first();
    }

    protected function findSalesOrder(string $id): ?SalesOrder
    {
        return SalesOrder::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
            ->first();
    }
}
