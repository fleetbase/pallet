<?php

namespace Fleetbase\Pallet\Http\Requests\Internal\v1;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the console's supplier create and update.
 *
 * Separate from the consumable API's CreateSupplierRequest because the two
 * authenticate differently: that one requires an API credential in the session,
 * which a console request does not carry.
 *
 * Without this the console accepted a completely empty submission, producing a
 * record with no name at all, which the list can only render as a bare dash.
 */
class CreateSupplierRequest extends FleetbaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => [Rule::requiredIf($this->isMethod('POST')), 'string', 'max:255'],
            'email' => 'nullable|email',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A supplier name is required.',
            'email.email'   => 'That email address is not valid.',
        ];
    }
}
