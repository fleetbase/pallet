<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Requests\CreateWarehouseZoneRequest;
use Fleetbase\Pallet\Http\Requests\UpdateWarehouseZoneRequest;
use Fleetbase\Pallet\Http\Resources\v1\DeletedResource;
use Fleetbase\Pallet\Http\Resources\v1\WarehouseZone as WarehouseZoneResource;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseZone;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseZoneController extends Controller
{
    protected array $writable = [
        'name',
        'code',
        'type',
        'status',
        'temperature_controlled',
        'temperature_range',
        'capacity',
        'meta',
    ];

    public function create(CreateWarehouseZoneRequest $request)
    {
        $warehouse = $this->resolveWarehouse($request);

        if ($warehouse instanceof JsonResponse) {
            return $warehouse;
        }

        $zone = WarehouseZone::create(array_merge($request->only($this->writable), [
            'company_uuid'   => session('company'),
            'warehouse_uuid' => $warehouse->uuid,
        ]));

        return new WarehouseZoneResource($zone->fresh(['warehouse']));
    }

    public function update($id, UpdateWarehouseZoneRequest $request)
    {
        try {
            $zone = WarehouseZone::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $zone->update($request->only($this->writable));

        return new WarehouseZoneResource($zone->fresh(['warehouse']));
    }

    public function query(Request $request)
    {
        return WarehouseZoneResource::collection(
            WarehouseZone::queryWithRequest($request, function ($query) {
                $query->with('warehouse');
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $zone = WarehouseZone::findRecordOrFail($id, ['warehouse']);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new WarehouseZoneResource($zone);
    }

    public function delete($id, Request $request)
    {
        try {
            $zone = WarehouseZone::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $zone->delete();

        return new DeletedResource($zone);
    }

    protected function resolveWarehouse(Request $request): Warehouse|JsonResponse
    {
        $warehouse = Warehouse::where('company_uuid', session('company'))
            ->where('public_id', $request->input('warehouse'))
            ->first();

        return $warehouse ?: response()->json(['error' => 'Warehouse not found.'], 404);
    }

    protected function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Warehouse zone resource not found.'], 404);
    }
}
