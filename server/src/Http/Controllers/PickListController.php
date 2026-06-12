<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\PickList as PickListResource;
use Fleetbase\Pallet\Models\PickList;
use Illuminate\Http\Request;

class PickListController extends PalletResourceController
{
    public $resource = 'pick-list';

    public function start(string $id)
    {
        $pickList = $this->findPickList($id);
        $pickList->start();

        return new PickListResource($pickList->fresh());
    }

    public function complete(string $id)
    {
        $pickList = $this->findPickList($id);
        $pickList->complete();

        return new PickListResource($pickList->fresh());
    }

    public function assign(Request $request, string $id)
    {
        $pickList = $this->findPickList($id);
        $pickList->assignTo($request->input('assigned_to_uuid'));

        return new PickListResource($pickList->fresh());
    }

    protected function findPickList(string $id): PickList
    {
        return PickList::where('company_uuid', session('company'))
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();
    }
}
