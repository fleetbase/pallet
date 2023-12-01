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
}
