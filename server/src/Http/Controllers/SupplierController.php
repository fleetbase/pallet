<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Requests\Internal\v1\CreateSupplierRequest;

class SupplierController extends PalletResourceController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'supplier';

    /**
     * Validation for console writes. validateRequest() only applies a request class
     * when this property is set, so without it internal creates were unvalidated and
     * an empty submission produced a supplier with no name at all.
     */
    public $createRequest = CreateSupplierRequest::class;

    public $updateRequest = CreateSupplierRequest::class;
}
