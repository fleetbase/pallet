<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Pallet\Models\WarehouseSection;
use Fleetbase\Pallet\Models\WarehouseAisle;
use Fleetbase\Pallet\Models\WarehouseBin;
use Fleetbase\Pallet\Models\WarehouseRack;
use Fleetbase\Pallet\Models\WarehouseDock;
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

    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);
            $record = $this->model->createRecordFromRequest($request, null, function ($request, $warehouse) {

                $docks = $request->array('warehouse.docks', []);
                foreach($docks as $dock) {
                    WarehouseDock::create((array_merge($dock, [
                        'warehouse_uuid' => data_get($warehouse, 'uuid'),
                        'company_uuid' => session('company'),
                        'created_by_uuid' => session('user')
                    ])));
                }

                $sections = $request->array('warehouse.sections', []);
                foreach ($sections as $section) {
                    $createdSection = WarehouseSection::create(array_merge($section, [
                        'warehouse_uuid' => data_get($warehouse, 'uuid'),
                        'company_uuid' => session('company'),
                        'created_by_uuid' => session('user')
                    ]));

                    $aisles = data_get($section, 'aisles', []);
                    foreach ($aisles as $aisle) {
                        $createdAisle = WarehouseAisle::create(array_merge($aisle, [
                            'section_uuid' => $createdSection->uuid,
                            'company_uuid' => session('company'),
                            'created_by_uuid' => session('user')
                        ]));

                        $racks = data_get($aisle, 'racks', []);
                        foreach ($racks as $rack) {
                            $createdRack = WarehouseRack::create(array_merge($rack, [
                                'aisle_uuid' => $createdAisle->uuid,
                                'company_uuid' => session('company'),
                                'created_by_uuid' => session('user')
                            ]));

                            $bins = data_get($rack, 'bins', []);
                            foreach ($bins as $bin) {
                                WarehouseBin::create(array_merge($bin, [
                                    'rack_uuid' => $createdRack->uuid,
                                    'company_uuid' => session('company'),
                                    'created_by_uuid' => session('user')
                                ]));
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

    public function updateRecord(Request $request, string $id)
    {
        try {
            $this->validateRequest($request);
            $record = $this->model->updateRecordFromRequest($request, $id, null, function ($request, $warehouse) {
                $docks = $request->array('warehouse.docks', []);
                foreach($docks as $dock) {
                    WarehouseDock::updateOrCreate(['uuid' => data_get($dock, 'uuid')], array_merge($dock, ['warehouse_uuid' => $warehouse->uuid, 'company_uuid' => session('company'), 'created_by_uuid' => session('user')]));
                }

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
