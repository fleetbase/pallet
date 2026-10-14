<?php

namespace Fleetbase\Pallet\Http\Requests;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

class CreateProductRequest extends FleetbaseRequest
{
    /**
     * The consumable API authenticates with an API credential rather than a user
     * session, so authorization is the presence of that credential.
     */
    public function authorize(): bool
    {
        return request()->session()->has('api_credential');
    }

    public function rules(): array
    {
        return [
            'name'                   => [Rule::requiredIf($this->isMethod('POST')), 'string', 'max:255'],
            // pallet_products carries a unique(company_uuid, sku). Without this rule the
            // insert reaches MySQL and answers 500 with a stack trace, where a caller
            // supplying a duplicate SKU deserves a 422 saying so.
            'sku'                    => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pallet_products', 'sku')
                    ->where('company_uuid', session('company'))
                    ->whereNull('deleted_at')
                    ->ignore($this->ignoredPublicId(), 'public_id'),
            ],
            'barcode'                => 'nullable|string|max:255',
            'internal_id'            => 'nullable|string|max:255',
            'description'            => 'nullable|string',
            'status'                 => 'nullable|string|max:255',
            'currency'               => 'nullable|string|size:3',
            'unit_cost'              => 'nullable|numeric|min:0',
            'unit_price'             => 'nullable|numeric|min:0',
            'sale_price'             => 'nullable|numeric|min:0',
            'declared_value'         => 'nullable|numeric|min:0',
            'weight'                 => 'nullable|numeric|min:0',
            'weight_unit'            => 'nullable|string|max:16',
            'length'                 => 'nullable|numeric|min:0',
            'width'                  => 'nullable|numeric|min:0',
            'height'                 => 'nullable|numeric|min:0',
            'dimensions_unit'        => 'nullable|string|max:16',
            'has_variants'           => 'nullable|boolean',
            'is_serialized'          => 'nullable|boolean',
            'is_lot_tracked'         => 'nullable|boolean',
            'is_kit'                 => 'nullable|boolean',
            'is_perishable'          => 'nullable|boolean',
            'requires_quality_check' => 'nullable|boolean',
            'reorder_point'          => 'nullable|integer|min:0',
            'reorder_quantity'       => 'nullable|integer|min:0',
            'shelf_life_days'        => 'nullable|integer|min:0',
            'supplier'               => 'nullable|string',
            'category'               => 'nullable|string',
            'meta'                   => 'nullable|array',
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
