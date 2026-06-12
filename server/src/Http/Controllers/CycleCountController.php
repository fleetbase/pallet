<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\CycleCount as CycleCountResource;
use Fleetbase\Pallet\Models\CycleCount;

class CycleCountController extends PalletResourceController
{
    public $resource = 'cycle-count';

    public function start(string $id)
    {
        $cycleCount = $this->findCycleCount($id);
        $cycleCount->start();

        return new CycleCountResource($cycleCount->fresh());
    }

    public function complete(string $id)
    {
        $cycleCount = $this->findCycleCount($id);
        $cycleCount->complete();

        return new CycleCountResource($cycleCount->fresh());
    }

    public function approve(string $id)
    {
        $cycleCount = $this->findCycleCount($id);
        $cycleCount->approve();

        return new CycleCountResource($cycleCount->fresh());
    }

    protected function findCycleCount(string $id): CycleCount
    {
        return CycleCount::where('company_uuid', session('company'))
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();
    }
}
