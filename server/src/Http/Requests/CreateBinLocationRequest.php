<?php

namespace Fleetbase\Pallet\Http\Requests;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

class CreateBinLocationRequest extends FleetbaseRequest
{
    public function authorize(): bool
    {
        return request()->session()->has('api_credential');
    }

    public function rules(): array
    {
        return [
            'warehouse'        => [Rule::requiredIf($this->isMethod('POST')), 'string'],
            'zone'             => 'nullable|string',
            'bin_number'       => [Rule::requiredIf($this->isMethod('POST')), 'string', 'max:255'],
            'barcode'          => 'nullable|string|max:255',
            'type'             => 'nullable|string|max:255',
            'status'           => 'nullable|string|max:255',
            'capacity'         => 'nullable|numeric|min:0',
            'dimensions'       => 'nullable',
            'is_pickable'      => 'nullable|boolean',
            'is_replenishable' => 'nullable|boolean',
            'priority'         => 'nullable|integer',
            'meta'             => 'nullable|array',
        ];
    }
}
