<?php

namespace Fleetbase\Pallet\Http\Requests;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

class CreateWarehouseRequest extends FleetbaseRequest
{
    public function authorize(): bool
    {
        return request()->session()->has('api_credential');
    }

    public function rules(): array
    {
        return [
            'name'            => [Rule::requiredIf($this->isMethod('POST')), 'string', 'max:255'],
            'code'            => 'nullable|string|max:255',
            'type'            => 'nullable|string|max:255',
            'status'          => 'nullable|string|max:255',
            'capacity'        => 'nullable|numeric|min:0',
            'floor_area_sqm'  => 'nullable|numeric|min:0',
            'operating_hours' => 'nullable',
            'timezone'        => 'nullable|string|max:64',
            'phone'           => 'nullable|string|max:64',
            'email'           => 'nullable|email',
            'total_docks'     => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
            'is_default'      => 'nullable|boolean',
            'meta'            => 'nullable|array',
        ];
    }
}
