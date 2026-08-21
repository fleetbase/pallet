<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\Internal\v1\Wave as WaveResource;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\Wave;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;

class WaveController extends PalletResourceController
{
    public $resource = 'wave';

    public function createRecord(Request $request)
    {
        $this->validateRequest($request);

        $data      = $request->input('wave');
        $warehouse = $this->findWarehouse(data_get($data, 'warehouse_uuid'));

        if (!$warehouse) {
            return response()->error('Select a warehouse for this wave.', 422);
        }

        $wave = new Wave([
            'company_uuid'   => session('company'),
            'warehouse_uuid' => $warehouse->uuid,
            'type'           => data_get($data, 'type', 'standard'),
            'status'         => data_get($data, 'status', 'pending'),
            'priority'       => data_get($data, 'priority', 5),
            'scheduled_at'   => data_get($data, 'scheduled_at'),
            'notes'          => data_get($data, 'notes'),
            'meta'           => data_get($data, 'meta', []),
        ]);

        $wave->save();

        if (Http::isInternalRequest($request)) {
            WaveResource::wrap($this->resourceSingularlName);
        }

        return new WaveResource($wave);
    }

    public function start(string $id)
    {
        $wave = $this->findWave($id);

        try {
            $wave->start();
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new WaveResource($wave->fresh(['warehouse', 'pickLists']));
    }

    public function release(string $id)
    {
        $wave = $this->findWave($id);

        try {
            $wave->release();
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new WaveResource($wave->fresh(['warehouse', 'pickLists']));
    }

    public function complete(string $id)
    {
        $wave = $this->findWave($id);

        try {
            $wave->complete();
        } catch (\RuntimeException $e) {
            return response()->error($e->getMessage(), 422);
        }

        return new WaveResource($wave->fresh(['warehouse', 'pickLists']));
    }

    protected function findWave(string $id): Wave
    {
        return Wave::where('company_uuid', session('company'))
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
