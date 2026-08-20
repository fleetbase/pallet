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
            'sku'            => 'nullable|string|max:255',
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
}
