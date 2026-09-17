<?php

namespace Fleetbase\Pallet\Http\Requests;

/**
 * Same field contract as creation, minus the required-on-POST rules — an update
 * may send any subset of the product's fields.
 */
class UpdateProductRequest extends CreateProductRequest
{
}
