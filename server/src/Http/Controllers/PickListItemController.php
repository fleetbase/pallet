<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\PickListItem as PickListItemResource;
use Fleetbase\Pallet\Models\PickListItem;
use Illuminate\Http\Request;

class PickListItemController extends PalletResourceController
{
    public $resource = 'pick-list-item';

    public function markPicked(Request $request, string $id)
    {
        $item = $this->findItem($id);
        $item->markPicked((int) $request->input('quantity_picked', $item->quantity_requested), $request->input('picked_by_uuid', session('user')));

        return new PickListItemResource($item->fresh());
    }

    protected function findItem(string $id): PickListItem
    {
        return PickListItem::where('company_uuid', session('company'))
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();
    }
}
