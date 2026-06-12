<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\Wave as WaveResource;
use Fleetbase\Pallet\Models\Wave;

class WaveController extends PalletResourceController
{
    public $resource = 'wave';

    public function start(string $id)
    {
        $wave = $this->findWave($id);
        $wave->start();

        return new WaveResource($wave->fresh());
    }

    public function release(string $id)
    {
        $wave = $this->findWave($id);
        $wave->release();

        return new WaveResource($wave->fresh());
    }

    public function complete(string $id)
    {
        $wave = $this->findWave($id);
        $wave->complete();

        return new WaveResource($wave->fresh());
    }

    protected function findWave(string $id): Wave
    {
        return Wave::where('company_uuid', session('company'))
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();
    }
}
