<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\CycleCountItem as CycleCountItemResource;
use Fleetbase\Pallet\Models\CycleCountItem;
use Illuminate\Http\Request;

class CycleCountItemController extends PalletResourceController
{
    public $resource = 'cycle-count-item';

    public function recordCount(Request $request, string $id)
    {
        $item = $this->findItem($id);
        $item->recordCount((int) $request->input('counted_quantity', 0), $request->input('counted_by_uuid', session('user')));

        return new CycleCountItemResource($item->fresh());
    }

    protected function findItem(string $id): CycleCountItem
    {
        return CycleCountItem::where('company_uuid', session('company'))
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();
    }
}
