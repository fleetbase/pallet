<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\IndexInventory;
use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Pallet\Models\Batch;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class InventoryController extends PalletResourceController
{
    public $resource = 'inventory';
    public function queryRecord(Request $request)
    {
        $single = $request->boolean('single');
        // sort set null as we handle via custom query
        $request->request->add(['sort' => null]);

        $data  = $this->model->queryFromRequest($request, function ($query) {
            // hotfix! fix the selected columns
            $queryBuilder = $query->getQuery();
            array_shift($queryBuilder->columns);

            // use summarize scope
            $query->summarizeByProduct();
        });

        if ($single) {
            $data = Arr::first($data);

            if (!$data) {
                return response()->error(Str::title($this->resourceSingularlName) . ' not found', 404);
            }

            if (Http::isInternalRequest($request)) {
                IndexInventory::wrap($this->resourceSingularlName);

                return new IndexInventory($data);
            }

            return new IndexInventory($data);
        }
        if (Http::isInternalRequest($request)) {
            IndexInventory::wrap($this->resourcePluralName);

            return IndexInventory::collection($data);
        }

        return IndexInventory::collection($data);
    }

    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);
            $data = $request->input('inventory');

            $batch = new Batch([
                'company_uuid' => session('company'),
                'created_by_uuid' => session('user'),
                'product_uuid' => data_get($data, 'product_uuid'),
                'batch_number' => data_get($data, 'batch_number', now()->format('Y-m-d')),
                'quantity' => data_get($data, 'quantity', 0),
                'expiry_date_at' => data_get($data, 'expiry_date_at'),
                'manufacture_date_at' => data_get($data, 'manufacture_date_at'),
            ]);
            $batch->save();

            $inventory = new Inventory([
                'company_uuid' => session('company'),
                'created_by_uuid' => session('user'),
                'product_uuid' => data_get($data, 'product_uuid'),
                'supplier_uuid' => data_get($data, 'supplier_uuid'),
                'warehouse_uuid' => data_get($data, 'warehouse_uuid'),
                'status' => data_get($data, 'status'),
                'quantity' => data_get($data, 'quantity', 0),
                'min_quantity' => data_get($data, 'min_quantity', 0),
                'comments' => data_get($data, 'comments'),
                'expiry_date_at' => data_get($data, 'expiry_date_at'),
            ]);

            $inventory->batch()->associate($batch);
            $inventory->save();

            if (Http::isInternalRequest($request)) {
                $this->resource::wrap($this->resourceSingularlName);
            }
            return new $this->resource($inventory);
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        }
    }
}
