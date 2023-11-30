<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Pallet\Http\Resources\IndexInventory;
use Fleetbase\Pallet\Models\Batch;
use Fleetbase\Pallet\Models\Inventory;
use Illuminate\Http\Request;
use Fleetbase\Support\Http;
use Illuminate\Database\QueryException;

class InventoryController extends PalletResourceController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'inventory';

    public function queryRecord(Request $request)
    {
        $limit = $request->input('limit');
        $data  = Inventory::summarizeByProduct()->paginate($limit);

        IndexInventory::wrap($this->resourcePluralName);

        return IndexInventory::collection($data);
    }

    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);
            $record = $this->model->createRecordFromRequest($request, null, function ($request, $inventory) {
                $batch = $request->array('inventory.batch', []);
                Batch::create(['uuid' => data_get($batch, 'uuid')], array_merge($batch, ['batch_number' =>('batch_number') ,'company_uuid' => session('company'), 'created_by_uuid' => session('user')]));
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
