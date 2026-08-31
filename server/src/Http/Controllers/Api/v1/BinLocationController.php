<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Requests\CreateBinLocationRequest;
use Fleetbase\Pallet\Http\Requests\UpdateBinLocationRequest;
use Fleetbase\Pallet\Http\Resources\v1\BinLocation as BinLocationResource;
use Fleetbase\Pallet\Http\Resources\v1\DeletedResource;
use Fleetbase\Pallet\Models\BinLocation;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseZone;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BinLocationController extends Controller
{
    protected array $relations = ['warehouse', 'zone', 'aisle', 'rack', 'section'];

    protected array $writable = [
        'bin_number',
        'barcode',
        'type',
        'status',
        'capacity',
        'dimensions',
        'is_pickable',
        'is_replenishable',
        'priority',
        'meta',
    ];

    public function create(CreateBinLocationRequest $request)
    {
        $placement = $this->resolvePlacement($request);

        if ($placement instanceof JsonResponse) {
            return $placement;
        }

        $bin = BinLocation::create(array_merge($request->only($this->writable), [
            'company_uuid'   => session('company'),
            'warehouse_uuid' => $placement['warehouse']->uuid,
            'zone_uuid'      => $placement['zone']?->uuid,
        ]));

        return new BinLocationResource($bin->fresh($this->relations));
    }

    public function update($id, UpdateBinLocationRequest $request)
    {
        try {
            $bin = BinLocation::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $bin->update($request->only($this->writable));

        return new BinLocationResource($bin->fresh($this->relations));
    }

    public function query(Request $request)
    {
        return BinLocationResource::collection(
            BinLocation::queryWithRequest($request, function ($query) {
                $query->with($this->relations);
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $bin = BinLocation::findRecordOrFail($id, $this->relations);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new BinLocationResource($bin);
    }

    public function delete($id, Request $request)
    {
        try {
            $bin = BinLocation::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $bin->delete();

        return new DeletedResource($bin);
    }

    /**
     * A zone must belong to the warehouse the bin is being placed in — otherwise the
     * bin would claim a position in two different buildings.
     */
    protected function resolvePlacement(Request $request): array|JsonResponse
    {
        $warehouse = Warehouse::where('company_uuid', session('company'))
            ->where('public_id', $request->input('warehouse'))
            ->first();

        if (!$warehouse) {
            return response()->json(['error' => 'Warehouse not found.'], 404);
        }

        $zone = null;

        if ($request->filled('zone')) {
            $zone = WarehouseZone::where('company_uuid', session('company'))
                ->where('warehouse_uuid', $warehouse->uuid)
                ->where('public_id', $request->input('zone'))
                ->first();

            if (!$zone) {
                return response()->json(['error' => 'Zone not found in this warehouse.'], 404);
            }
        }

        return ['warehouse' => $warehouse, 'zone' => $zone];
    }

    protected function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Bin location resource not found.'], 404);
    }
}
