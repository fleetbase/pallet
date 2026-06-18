<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Pallet\Http\Resources\WarehouseZone as WarehouseZoneResource;
use Fleetbase\Pallet\Models\Warehouse;
use Fleetbase\Pallet\Models\WarehouseZone;
use Fleetbase\Support\Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class WarehouseZoneController extends PalletResourceController
{
    public $resource = 'warehouse-zone';

    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);

            $data          = $request->input('warehouse_zone', $request->input('warehouse-zone', $request->all()));
            $warehouseUuid = data_get($data, 'warehouse_uuid');

            if (!$warehouseUuid) {
                return response()->error('A warehouse is required to create a zone.', 422);
            }

            $warehouse = Warehouse::where('company_uuid', session('company'))
                ->where(fn ($query) => $query->where('uuid', $warehouseUuid)->orWhere('public_id', $warehouseUuid))
                ->first();
            if (!$warehouse) {
                return response()->error('The selected warehouse could not be found.', 404);
            }

            if (!data_get($data, 'name')) {
                return response()->error('A zone name is required.', 422);
            }

            $zone = WarehouseZone::create([
                'company_uuid'            => session('company'),
                'warehouse_uuid'          => $warehouse->uuid,
                'name'                    => data_get($data, 'name'),
                'code'                    => data_get($data, 'code'),
                'type'                    => data_get($data, 'type', 'storage'),
                'status'                  => data_get($data, 'status', 'active'),
                'temperature_controlled'  => (bool) data_get($data, 'temperature_controlled', false),
                'temperature_range'       => data_get($data, 'temperature_range'),
                'capacity'                => data_get($data, 'capacity', 0),
                'current_utilization'     => data_get($data, 'current_utilization', 0),
                'meta'                    => data_get($data, 'meta'),
            ]);

            if (Http::isInternalRequest($request)) {
                WarehouseZoneResource::wrap($this->resourceSingularlName);
            }

            return new WarehouseZoneResource($zone->fresh(['warehouse']));
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        }
    }
}
