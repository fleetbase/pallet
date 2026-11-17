<?php

namespace Fleetbase\Pallet\Http\Requests;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

class CreateSupplierRequest extends FleetbaseRequest
{
    public function authorize(): bool
    {
        return request()->session()->has('api_credential');
    }

    public function rules(): array
    {
        return [
            'name'        => [Rule::requiredIf($this->isMethod('POST')), 'string', 'max:255'],
            'email'       => 'nullable|email',
            'phone'       => 'nullable|string|max:64',
            'type'        => 'nullable|string|max:255',
            'status'      => 'nullable|string|max:255',
            'country'     => 'nullable|string|max:8',
            'website_url' => 'nullable|string|max:255',
            'internal_id' => 'nullable|string|max:255',
            'meta'        => 'nullable|array',
        ];
    }
}
