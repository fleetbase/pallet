<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class PurchaseOrderController extends PalletResourceController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'purchase-order';

    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);
            $data = $request->input('purchaseOrder');

            $salesOrder = new PurchaseOrder([
                'company_uuid' => session('company'),
                'created_by_uuid' => session('user'),
                'supplier_uuid' => data_get($data, 'supplier_uuid'),
                'transaction_uuid' => data_get($data, 'transaction_uuid'),
                'assigned_to_uuid' => data_get($data, 'user'),
                'status' => data_get($data, 'status'),
                'reference_code' => data_get($data, 'reference_code'),
                'reference_url' => data_get($data, 'reference_url'),
                'description' => data_get($data, 'description'),
                'comments' => data_get($data, 'comments'),
                'expected_delivery_at' => data_get($data, 'expected_delivery_at'),
                'order_date_at' => data_get($data, 'order_date_at'),
            ]);
            $salesOrder->save();

            if (Http::isInternalRequest($request)) {
                $this->resource::wrap($this->resourceSingularlName);
            }
            return new $this->resource($salesOrder);
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        } catch (QueryException $e) {
            return response()->error($e->getMessage());
        } catch (FleetbaseRequestValidationException $e) {
            return response()->error($e->getErrors());
        }
    }
}
