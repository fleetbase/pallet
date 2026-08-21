<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

/**
 * Console representation of a supplier.
 *
 * Pallet never declared one: Supplier extends the FleetOps Vendor, and resource
 * resolution simply fell through to the generic FleetbaseResource, which emits the
 * model's attributes wholesale — uuid and public_id included, which is what Ember
 * Data needs to build a record.
 *
 * Adding the consumable Http\Resources\v1\Supplier put a class in front of that
 * fallback, so the suppliers listing began serving the public shape and the screen
 * died with "You must include an 'id' for supplier". This restores the previous
 * behaviour explicitly rather than relying on a fallback that a sibling class can
 * intercept.
 */
class Supplier extends FleetbaseResource
{
}
