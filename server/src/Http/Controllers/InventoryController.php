<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\IndexInventory;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
}
