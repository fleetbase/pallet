<?php

namespace Fleetbase\Pallet\Http\Requests\Internal\v1;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the console's product create and update.
 *
 * Separate from the consumable API's CreateProductRequest because the two
 * authenticate differently: that one requires an API credential in the session,
 * which a console request does not carry.
 *
 * Without this the console accepted a completely empty submission, producing a
 * record with no name and no SKU that the list could only render as
 * "Untitled product".
 */
class CreateProductRequest extends FleetbaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [Rule::requiredIf($this->isMethod('POST')), 'string', 'max:255'],
            'sku'  => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pallet_products', 'sku')
                    ->where('company_uuid', session('company'))
                    ->whereNull('deleted_at')
                    ->ignore($this->input('uuid'), 'uuid'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A product name is required.',
            'sku.unique'    => 'That SKU is already in use by another product.',
        ];
    }
}
