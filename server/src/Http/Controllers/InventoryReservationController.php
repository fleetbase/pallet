<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\InventoryReservation as InventoryReservationResource;
use Fleetbase\Pallet\Models\InventoryReservation;

class InventoryReservationController extends PalletResourceController
{
    public $resource = 'inventory-reservation';

    public function release(string $id)
    {
        $reservation = $this->findReservation($id);
        $reservation->release();

        return new InventoryReservationResource($reservation->fresh());
    }

    public function fulfill(string $id)
    {
        $reservation = $this->findReservation($id);
        $reservation->fulfill();

        return new InventoryReservationResource($reservation->fresh());
    }

    protected function findReservation(string $id): InventoryReservation
    {
        return InventoryReservation::where('company_uuid', session('company'))
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();
    }
}
