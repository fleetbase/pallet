<?php

namespace Fleetbase\Pallet\Http\Requests;

class CreateStockTransferRequest extends \Fleetbase\Http\Requests\FleetbaseRequest
{
    public function authorize(): bool
    {
        return request()->session()->has('api_credential');
    }

    public function rules(): array
    {
        return [
            'from_warehouse'   => 'required|string',
            'to_warehouse'     => 'required|string|different:from_warehouse',
            'type'             => 'nullable|string|max:255',
            'notes'            => 'nullable|string',
            'meta'             => 'nullable|array',
            'items'            => 'nullable|array',
            'items.*.product'  => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ];
    }
}
