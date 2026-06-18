<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Pallet\Http\Resources\BinLocation as BinLocationResource;
use Fleetbase\Pallet\Models\BinLocation;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseZone;
use Fleetbase\Support\Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class BinLocationController extends PalletResourceController
{
    public $resource = 'bin-location';

    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);

            $data          = $request->input('bin_location', $request->input('bin-location', $request->all()));
            $warehouseUuid = data_get($data, 'warehouse_uuid');
            $zoneUuid      = data_get($data, 'zone_uuid');

            if (!$warehouseUuid) {
                return response()->error('A warehouse is required to create a bin location.', 422);
            }

            $warehouse = Warehouse::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $warehouseUuid)->orWhere('public_id', $warehouseUuid))
                ->first();
            if (!$warehouse) {
                return response()->error('The selected warehouse could not be found.', 404);
            }

            if ($zoneUuid) {
                $zone = WarehouseZone::where('company_uuid', session('company'))
                    ->where('warehouse_uuid', $warehouse->uuid)
                    ->where(fn ($query) => $query->where('uuid', $zoneUuid)->orWhere('public_id', $zoneUuid))
                    ->first();

                if (!$zone) {
                    return response()->error('The selected zone could not be found for this warehouse.', 404);
                }
            }

            if (!data_get($data, 'bin_number')) {
                return response()->error('A bin number is required.', 422);
            }

            $location = BinLocation::create([
                'company_uuid'       => session('company'),
                'warehouse_uuid'     => $warehouse->uuid,
                'zone_uuid'          => $zoneUuid,
                'aisle_uuid'         => data_get($data, 'aisle_uuid'),
                'rack_uuid'          => data_get($data, 'rack_uuid'),
                'section_uuid'       => data_get($data, 'section_uuid'),
                'bin_number'         => data_get($data, 'bin_number'),
                'barcode'            => data_get($data, 'barcode'),
                'type'               => data_get($data, 'type', 'storage'),
                'status'             => data_get($data, 'status', 'active'),
                'capacity'           => data_get($data, 'capacity', 0),
                'current_volume'     => data_get($data, 'current_volume', 0),
                'dimensions'         => data_get($data, 'dimensions'),
                'is_pickable'        => (bool) data_get($data, 'is_pickable', true),
                'is_replenishable'   => (bool) data_get($data, 'is_replenishable', true),
                'priority'           => data_get($data, 'priority', 0),
                'meta'               => data_get($data, 'meta'),
            ]);

            if (Http::isInternalRequest($request)) {
                BinLocationResource::wrap($this->resourceSingularlName);
            }

            return new BinLocationResource($location->fresh(['warehouse', 'zone']));
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        }
    }
}
