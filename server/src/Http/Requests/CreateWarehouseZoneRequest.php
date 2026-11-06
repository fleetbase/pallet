<?php

namespace Fleetbase\Pallet\Http\Requests;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

class CreateWarehouseZoneRequest extends FleetbaseRequest
{
    public function authorize(): bool
    {
        return request()->session()->has('api_credential');
    }

    public function rules(): array
    {
        return [
            'warehouse'              => [Rule::requiredIf($this->isMethod('POST')), 'string'],
            'name'                   => [Rule::requiredIf($this->isMethod('POST')), 'string', 'max:255'],
            'code'                   => 'nullable|string|max:255',
            'type'                   => 'nullable|string|max:255',
            'status'                 => 'nullable|string|max:255',
            'temperature_controlled' => 'nullable|boolean',
            'temperature_range'      => 'nullable',
            'capacity'               => 'nullable|numeric|min:0',
            'meta'                   => 'nullable|array',
        ];
    }
}
