<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\Internal\v1\CycleCountItem as CycleCountItemResource;
use Fleetbase\Pallet\Models\CycleCountItem;
use Illuminate\Http\Request;

class CycleCountItemController extends PalletResourceController
{
    public $resource = 'cycle-count-item';

    public function recordCount(Request $request, string $id)
    {
        $item            = $this->findItem($id);
        $countedQuantity = (int) $request->input('counted_quantity', 0);

        if ($countedQuantity < 0) {
            return response()->error('Counted quantity must be zero or greater.', 422);
        }

        $item->recordCount($countedQuantity, $request->input('counted_by_uuid', session('user')));

        return new CycleCountItemResource($item->fresh(['product', 'variant', 'inventory', 'binLocation']));
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
