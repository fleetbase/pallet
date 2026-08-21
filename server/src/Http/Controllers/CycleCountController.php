<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\Internal\v1\CycleCount as CycleCountResource;
use Fleetbase\Pallet\Models\CycleCount;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseZone;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;

class CycleCountController extends PalletResourceController
{
    public $resource = 'cycle-count';

    public function createRecord(Request $request)
    {
        $this->validateRequest($request);

        $data      = $request->input('cycle_count');
        $warehouse = $this->findWarehouse(data_get($data, 'warehouse_uuid'));

        if (!$warehouse) {
            return response()->error('Select a warehouse for this cycle count.', 422);
        }

        $zoneId = data_get($data, 'zone_uuid');
        $zone   = $zoneId ? $this->findZone($zoneId, $warehouse->uuid) : null;

        if ($zoneId && !$zone) {
            return response()->error('Selected zone does not belong to this warehouse.', 422);
        }

        $cycleCount = new CycleCount([
            'company_uuid'      => session('company'),
            'warehouse_uuid'    => $warehouse->uuid,
            'zone_uuid'         => $zone?->uuid,
            'assigned_to_uuid'  => data_get($data, 'assigned_to_uuid'),
            'type'              => data_get($data, 'type', 'standard'),
            'status'            => data_get($data, 'status', 'pending'),
            'scheduled_at'      => data_get($data, 'scheduled_at'),
            'notes'             => data_get($data, 'notes'),
            'meta'              => data_get($data, 'meta', []),
        ]);

        $cycleCount->save();

        if (Http::isInternalRequest($request)) {
            CycleCountResource::wrap($this->resourceSingularlName);
        }

        return new CycleCountResource($cycleCount);
    }

    public function start(string $id)
    {
        $cycleCount = $this->findCycleCount($id);

        try {
            $cycleCount->start();
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new CycleCountResource($cycleCount->fresh(['warehouse', 'zone', 'assignedTo', 'items']));
    }

    public function complete(string $id)
    {
        $cycleCount = $this->findCycleCount($id);

        try {
            $cycleCount->complete();
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new CycleCountResource($cycleCount->fresh(['warehouse', 'zone', 'assignedTo', 'items']));
    }

    public function approve(string $id)
    {
        $cycleCount = $this->findCycleCount($id);

        try {
            $cycleCount->approve();
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new CycleCountResource($cycleCount->fresh(['warehouse', 'zone', 'assignedTo', 'items']));
    }

    protected function findCycleCount(string $id): CycleCount
    {
        return CycleCount::where('company_uuid', session('company'))
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

    protected function findZone(string $id, string $warehouseUuid): ?WarehouseZone
    {
        return WarehouseZone::where('company_uuid', session('company'))
            ->where('warehouse_uuid', $warehouseUuid)
            ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
            ->first();
    }
}
