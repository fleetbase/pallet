<?php

namespace Fleetbase\Pallet\Http\Requests;

/**
 * Same field contract as creation. Lines are managed through the order's own
 * endpoints rather than replaced wholesale by an update.
 */
class UpdateSalesOrderRequest extends CreateSalesOrderRequest
{
}
