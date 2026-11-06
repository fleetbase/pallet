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
            // warehouses.code is unique across the whole table, not per company.
            'code'            => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pallet_warehouses', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($this->ignoredPublicId(), 'public_id'),
            ],
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

    /**
     * The record being updated, so its own value does not collide with itself.
     * Route::parameter() is used rather than route('id') because a create request
     * has no such parameter and the latter throws when the route lacks it.
     */
    protected function ignoredPublicId(): ?string
    {
        $route = $this->route();

        // hasParameters() guards an unbound route: parameters() throws on one, which
        // a manually constructed request (tests, internal dispatch) will always hit.
        if (!$route || !$route->hasParameters()) {
            return null;
        }

        return $route->parameter('id');
    }
}
