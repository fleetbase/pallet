<?php

namespace Fleetbase\Pallet\Http\Requests;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

class CreateProductVariantRequest extends FleetbaseRequest
{
    public function authorize(): bool
    {
        return request()->session()->has('api_credential');
    }

    public function rules(): array
    {
        return [
            'product'        => [Rule::requiredIf($this->isMethod('POST')), 'string'],
            'name'           => [Rule::requiredIf($this->isMethod('POST')), 'string', 'max:255'],
            'sku'            => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pallet_product_variants', 'sku')
                    ->where('company_uuid', session('company'))
                    ->whereNull('deleted_at')
                    ->ignore($this->ignoredPublicId(), 'public_id'),
            ],
            'barcode'        => 'nullable|string|max:255',
            'option_values'  => 'nullable|array',
            'currency'       => 'nullable|string|size:3',
            'unit_cost'      => 'nullable|numeric|min:0',
            'unit_price'     => 'nullable|numeric|min:0',
            'sale_price'     => 'nullable|numeric|min:0',
            'declared_value' => 'nullable|numeric|min:0',
            'weight'         => 'nullable|numeric|min:0',
            'weight_unit'    => 'nullable|string|max:16',
            'status'         => 'nullable|string|max:255',
            'meta'           => 'nullable|array',
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
