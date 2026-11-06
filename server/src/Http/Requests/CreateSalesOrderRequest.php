<?php

namespace Fleetbase\Pallet\Http\Requests;

use Fleetbase\Http\Requests\FleetbaseRequest;

class CreateSalesOrderRequest extends FleetbaseRequest
{
    public function authorize(): bool
    {
        return request()->session()->has('api_credential');
    }

    public function rules(): array
    {
        return [
            'customer'                => 'nullable|string',
            'supplier'                => 'nullable|string',
            'warehouse'               => 'nullable|string',
            'status'                  => 'nullable|string|max:255',
            'customer_reference_code' => 'nullable|string|max:255',
            'reference_code'          => 'nullable|string|max:255',
            'reference_url'           => 'nullable|string|max:255',
            'description'             => 'nullable|string',
            'comments'                => 'nullable|string',
            'order_date_at'           => 'nullable|date',
            'expected_delivery_at'    => 'nullable|date',
            'meta'                    => 'nullable|array',
            'items'                   => 'nullable|array',
            'items.*.product'         => 'required_with:items|string',
            'items.*.quantity'        => 'required_with:items|integer|min:1',
            'items.*.unit_price'      => 'nullable|numeric|min:0',
            'items.*.warehouse'       => 'nullable|string',
        ];
    }
}
