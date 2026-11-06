<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\Internal\v1\CycleCount as CycleCountResource;
use Fleetbase\Pallet\Models\AuditEventType;
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

    /**
     * Reveal the expected quantities on an open count, and record that it happened.
     *
     * Counting is blind: `CycleCountItem`'s resource withholds expected quantity and
     * variance while the count is in progress, so the figures are not merely hidden in
     * the interface but absent from the payload.
     *
     * A supervisor can still need them — a line that will not reconcile, a suspected
     * mis-scan. The accepted design decision allows that on one condition: the reveal
     * is written to the audit trail, which is what keeps the count defensible
     * afterwards. Anyone reviewing the count can see that the numbers were shown, to
     * whom and when, and weigh the result accordingly.
     *
     * Revealing on a count that is already closed is not an error and is not recorded —
     * the figures are public by then, and logging it would fill the trail with events
     * that carry no meaning.
     */
    public function revealExpected(Request $request, string $id)
    {
        $cycleCount = $this->findCycleCount($id);

        if ($cycleCount->status === 'in_progress') {
            $cycleCount->logAuditEvent(
                AuditEventType::CYCLE_COUNT,
                'Expected Quantities Revealed',
                'expected_revealed',
                $request->input('reason'),
                [
                    'count_number'   => $cycleCount->count_number,
                    'warehouse_uuid' => $cycleCount->warehouse_uuid,
                    'counted_items'  => $cycleCount->counted_items,
                    'total_items'    => $cycleCount->total_items,
                ]
            );
        }

        // Tells the item resource to include the figures it would otherwise withhold.
        $request->attributes->set('pallet.expected_visible', true);

        return new CycleCountResource($cycleCount->fresh(['warehouse', 'zone', 'assignedTo', 'items.product', 'items.binLocation']));
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
