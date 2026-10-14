<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Requests\CreateWarehouseRequest;
use Fleetbase\Pallet\Http\Requests\UpdateWarehouseRequest;
use Fleetbase\Pallet\Http\Resources\v1\DeletedResource;
use Fleetbase\Pallet\Http\Resources\v1\Warehouse as WarehouseResource;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Company, creator and place linkage are assigned by Pallet, not by the caller.
     */
    protected array $writable = [
        'name',
        'code',
        'type',
        'status',
        'capacity',
        'floor_area_sqm',
        'operating_hours',
        'timezone',
        'phone',
        'email',
        'total_docks',
        'is_active',
        'is_default',
        'meta',
    ];

    public function create(CreateWarehouseRequest $request)
    {
        $input                 = $request->only($this->writable);
        $input['company_uuid'] = session('company');

        return new WarehouseResource(Warehouse::create($input)->fresh(['place']));
    }

    public function update($id, UpdateWarehouseRequest $request)
    {
        try {
            $warehouse = Warehouse::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $warehouse->update($request->only($this->writable));

        return new WarehouseResource($warehouse->fresh(['place']));
    }

    /**
     * The stock item count is loaded here rather than on the model: $withCount does
     * not survive searchBuilder()'s select(['*']).
     */
    public function query(Request $request)
    {
        return WarehouseResource::collection(
            Warehouse::queryWithRequest($request, function ($query) {
                $query->withCount('inventories');
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $warehouse = Warehouse::findRecordOrFail($id, ['place'], ['*'], function ($query) {
                $query->withCount('inventories');
            });
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new WarehouseResource($warehouse);
    }

    public function delete($id, Request $request)
    {
        try {
            $warehouse = Warehouse::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $warehouse->delete();

        return new DeletedResource($warehouse);
    }

    protected function notFound(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['error' => 'Warehouse resource not found.'], 404);
    }
}
