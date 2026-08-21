<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\FleetOps\Models\Place;
use Fleetbase\LaravelMysqlSpatial\Types\Point as SpatialPoint;
use Fleetbase\Pallet\Models\WarehouseAisle;
use Fleetbase\Pallet\Models\WarehouseBin;
use Fleetbase\Pallet\Models\WarehouseDock;
use Fleetbase\Pallet\Models\WarehouseRack;
use Fleetbase\Pallet\Models\WarehouseSection;
use Fleetbase\Support\Http;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class WarehouseController extends PalletResourceController
{
    /**
     * The stock items count is requested per query path rather than declared on the
     * model. A model-level $withCount would apply to every Warehouse query including
     * the metrics GROUP BY aggregates, where Eloquent's added `table.*` breaks
     * only_full_group_by on MySQL.
     */
    public function onQueryRecord($query)
    {
        $query->withCount('inventories');
    }

    /**
     * The details panel reads the same count, and findRecordOrFail does not inherit
     * the listing callback.
     */
    public function onFindRecord($query)
    {
        $query->withCount('inventories');
    }

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
            $record = $this->model->createRecordFromRequest($request, function ($request, &$input) {
                // Build place data from address fields and create a linked Place
                $placeData = $request->input('warehouse.place', []);
                if (empty($placeData)) {
                    $placeData = array_filter([
                        'name'         => $request->input('warehouse.name'),
                        'street1'      => $request->input('warehouse.street1'),
                        'street2'      => $request->input('warehouse.street2'),
                        'city'         => $request->input('warehouse.city'),
                        'province'     => $request->input('warehouse.province'),
                        'postal_code'  => $request->input('warehouse.postal_code'),
                        'country'      => $request->input('warehouse.country'),
                        'neighborhood' => $request->input('warehouse.neighborhood'),
                        'district'     => $request->input('warehouse.district'),
                        'building'     => $request->input('warehouse.building'),
                        'location'     => $request->input('warehouse.location'),
                    ]);
                }
                // `name` is always present, so array_filter never leaves $placeData
                // empty and a Place was created even when no address was entered.
                // Utils::getAddressStringForPlace lists `name` first among the address
                // parts and uppercases each one, so the warehouses list rendered its
                // own name, shouted, in the ADDRESS column. Only create a place when
                // something that is actually an address was supplied.
                $hasAddress = (bool) array_diff_key($placeData, ['name' => null]);

                if ($hasAddress) {
                    // places.location is NOT NULL with no default, and array_filter
                    // strips a null location — supply the Fleetbase default point
                    $placeData['location'] = $placeData['location'] ?? new SpatialPoint(0, 0);
                    $place                 = Place::create(array_merge($placeData, [
                        'company_uuid'    => session('company'),
                        'created_by_uuid' => session('user'),
                        'type'            => 'pallet-warehouse',
                    ]));
                    $input['place_uuid'] = $place->uuid;
                }
            }, function ($request, $warehouse) {
                $docks = $request->array('warehouse.docks', []);
                foreach ($docks as $dock) {
                    WarehouseDock::create(array_merge($dock, [
                        'warehouse_uuid'  => $warehouse->uuid,
                        'company_uuid'    => session('company'),
                        'created_by_uuid' => session('user'),
                    ]));
                }

                $sections = $request->array('warehouse.sections', []);
                foreach ($sections as $section) {
                    $createdSection = WarehouseSection::create(array_merge($section, [
                        'warehouse_uuid'  => $warehouse->uuid,
                        'company_uuid'    => session('company'),
                        'created_by_uuid' => session('user'),
                    ]));

                    $aisles = data_get($section, 'aisles', []);
                    foreach ($aisles as $aisle) {
                        $createdAisle = WarehouseAisle::create(array_merge($aisle, [
                            'section_uuid'    => $createdSection->uuid,
                            'company_uuid'    => session('company'),
                            'created_by_uuid' => session('user'),
                        ]));

                        $racks = data_get($aisle, 'racks', []);
                        foreach ($racks as $rack) {
                            $createdRack = WarehouseRack::create(array_merge($rack, [
                                'aisle_uuid'      => $createdAisle->uuid,
                                'company_uuid'    => session('company'),
                                'created_by_uuid' => session('user'),
                            ]));

                            $bins = data_get($rack, 'bins', []);
                            foreach ($bins as $bin) {
                                WarehouseBin::create(array_merge($bin, [
                                    'rack_uuid'       => $createdRack->uuid,
                                    'company_uuid'    => session('company'),
                                    'created_by_uuid' => session('user'),
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
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        }
    }

    public function updateRecord(Request $request, string $id)
    {
        try {
            $this->validateRequest($request);
            $record = $this->model->updateRecordFromRequest($request, $id, function ($request, &$input, $warehouse) {
                // Update or create the linked Place
                $placeData = $request->input('warehouse.place', []);
                if (empty($placeData)) {
                    $placeData = array_filter([
                        'name'         => $request->input('warehouse.name'),
                        'street1'      => $request->input('warehouse.street1'),
                        'street2'      => $request->input('warehouse.street2'),
                        'city'         => $request->input('warehouse.city'),
                        'province'     => $request->input('warehouse.province'),
                        'postal_code'  => $request->input('warehouse.postal_code'),
                        'country'      => $request->input('warehouse.country'),
                        'neighborhood' => $request->input('warehouse.neighborhood'),
                        'district'     => $request->input('warehouse.district'),
                        'building'     => $request->input('warehouse.building'),
                        'location'     => $request->input('warehouse.location'),
                    ]);
                }
                // Same guard as createRecord: a name on its own is not an address.
                // An existing place still gets its name kept in step.
                $hasAddress = (bool) array_diff_key($placeData, ['name' => null]);

                if ($warehouse->place_uuid) {
                    Place::where('uuid', $warehouse->place_uuid)->update($placeData);
                } elseif ($hasAddress) {
                    // see createRecord: location is NOT NULL with no default
                    $placeData['location'] = $placeData['location'] ?? new SpatialPoint(0, 0);
                    $place                 = Place::create(array_merge($placeData, [
                        'company_uuid'    => session('company'),
                        'created_by_uuid' => session('user'),
                        'type'            => 'pallet-warehouse',
                    ]));
                    $input['place_uuid'] = $place->uuid;
                }
            }, function ($request, $warehouse) {
                $docks = $request->array('warehouse.docks', []);
                foreach ($docks as $dock) {
                    WarehouseDock::updateOrCreate(
                        ['uuid' => data_get($dock, 'uuid')],
                        array_merge($dock, ['warehouse_uuid' => $warehouse->uuid, 'company_uuid' => session('company'), 'created_by_uuid' => session('user')])
                    );
                }

                $sections = $request->array('warehouse.sections', []);
                foreach ($sections as $section) {
                    WarehouseSection::updateOrCreate(
                        ['uuid' => data_get($section, 'uuid')],
                        array_merge($section, ['warehouse_uuid' => $warehouse->uuid, 'company_uuid' => session('company'), 'created_by_uuid' => session('user')])
                    );

                    $aisles = data_get($section, 'aisles', []);
                    foreach ($aisles as $aisle) {
                        WarehouseAisle::updateOrCreate(
                            ['uuid' => data_get($aisle, 'uuid')],
                            array_merge($aisle, ['section_uuid' => data_get($section, 'uuid'), 'company_uuid' => session('company'), 'created_by_uuid' => session('user')])
                        );

                        $racks = data_get($aisle, 'racks', []);
                        foreach ($racks as $rack) {
                            WarehouseRack::updateOrCreate(
                                ['uuid' => data_get($rack, 'uuid')],
                                array_merge($rack, ['aisle_uuid' => data_get($aisle, 'uuid'), 'company_uuid' => session('company'), 'created_by_uuid' => session('user')])
                            );

                            $bins = data_get($rack, 'bins', []);
                            foreach ($bins as $bin) {
                                WarehouseBin::updateOrCreate(
                                    ['uuid' => data_get($bin, 'uuid')],
                                    array_merge($bin, ['rack_uuid' => data_get($rack, 'uuid'), 'company_uuid' => session('company'), 'created_by_uuid' => session('user')])
                                );
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
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        }
    }
}
