<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Exceptions\FleetbaseRequestValidationException;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class SalesOrderController extends PalletResourceController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'sales-order';

    public function createRecord(Request $request)
    {
        try {
            $this->validateRequest($request);
            $data = $request->input('sales_order');

            $salesOrder = new SalesOrder([
                'company_uuid'              => session('company'),
                'created_by_uuid'           => session('user'),
                'supplier_uuid'             => data_get($data, 'supplier_uuid'),
                'transaction_uuid'          => data_get($data, 'transaction_uuid'),
                'assigned_to_uuid'          => data_get($data, 'assigned_to_uuid'),
                'point_of_contact_uuid'     => data_get($data, 'point_of_contact_uuid'),
                'status'                    => data_get($data, 'status', 'pending'),
                'reference_code'            => data_get($data, 'reference_code'),
                'reference_url'             => data_get($data, 'reference_url'),
                'customer_reference_code'   => data_get($data, 'customer_reference_code'),
                'description'               => data_get($data, 'description'),
                'comments'                  => data_get($data, 'comments'),
                'currency'                  => data_get($data, 'currency'),
                'meta'                      => data_get($data, 'meta', []),
                'expected_delivery_at'      => data_get($data, 'expected_delivery_at'),
                'order_date_at'             => data_get($data, 'order_date_at', now()),
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
