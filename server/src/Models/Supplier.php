<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\FleetOps\Models\Vendor;

class Supplier extends Vendor
{
    /**
     * Overwrite both vendor resource name with `payloadKey`
     *
     * @var string
     */
    protected $payloadKey = 'supplier';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'supplier';
}
