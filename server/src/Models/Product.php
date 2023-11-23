<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\FleetOps\Models\Entity;

class Product extends Entity
{
    /**
     * Overwrite both entity resource name with `payloadKey`
     *
     * @var string
     */
    protected $payloadKey = 'product';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    public $publicIdType = 'product';

    /**
     * Filterable parameters.
     *
     * @var array
     */
    protected $filterParams = ['facilitator', 'facilitator_type'];
}
