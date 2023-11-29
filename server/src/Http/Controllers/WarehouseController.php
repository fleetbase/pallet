<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Pallet\Models\WarehouseSection;
use Fleetbase\Pallet\Models\WarehouseAisle;
use Fleetbase\Pallet\Models\WarehouseBin;
use Fleetbase\Pallet\Models\WarehouseRack;
use Fleetbase\Support\Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class WarehouseController extends PalletResourceController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'warehouse';

    public function updateRecord(Request $request, string $id)
    {
        try {
            $this->validateRequest($request);
            $record = $this->model->updateRecordFromRequest($request, $id, null, function ($request, $warehouse) {
                $sections = $request->array('warehouse.sections', []);

                foreach ($sections as $section) {
                    WarehouseSection::updateOrCreate(['uuid' => data_get($section, 'uuid')], array_merge($section, ['warehouse_uuid' => $warehouse->uuid, 'company_uuid' => session('company'), 'created_by_uuid' => session('user')]));

                    $aisles = data_get($section, 'aisles', []);
                    foreach ($aisles as $aisle) {
                        WarehouseAisle::updateOrCreate(['uuid' => data_get($aisle, 'uuid')], array_merge($aisle, ['section_uuid' => data_get($section, 'uuid'), 'company_uuid' => session('company'), 'created_by_uuid' => session('user')]));
                        
                        $racks = data_get($aisle, 'racks', []);
                        foreach ($racks as $rack) {
                            WarehouseRack::updateOrCreate(['uuid' => data_get($rack, 'uuid')], array_merge($rack, ['aisle_uuid' => data_get($aisle, 'uuid'), 'company_uuid' => session('company'), 'created_by_uuid' => session('user')]));

                            $bins = data_get($rack, 'bins', []);
                            foreach ($bins as $bin) {
                                WarehouseBin::updateOrCreate(['uuid' => data_get($bin, 'uuid')], array_merge($bin, ['rack_uuid' => data_get($rack, 'uuid'), 'company_uuid' => session('company'), 'created_by_uuid' => session('user')]));
                            }
                        }
                    }
                }
            });

            if (Http::isInternalRequest($request)) {
                $this->resource::wrap($this->resourceSingularlName);

                return new $this->resource($record);
            }

            return new $this->resource($record);
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        }
    }
}
