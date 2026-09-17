<?php

namespace Fleetbase\Pallet\Http\Requests;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

class CreateStockAdjustmentRequest extends FleetbaseRequest
{
    public function authorize(): bool
    {
        return request()->session()->has('api_credential');
    }

    public function rules(): array
    {
        return [
            'product'   => 'required|string',
            'warehouse' => 'required|string',
            'variant'   => 'nullable|string',
            'quantity'  => 'required|integer|min:1',
            'type'      => ['nullable', Rule::in(['add', 'remove', 'correction'])],
            'reason'    => 'nullable|string',
        ];
    }
}
