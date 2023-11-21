<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Models\Model;

class PurchaseOrder extends Model
{
    use HasUuid, HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_purchase_orders';

    /**
     * The singularName overwrite.
     *
     * @var string
     */
    protected $singularName = 'purchase-order';

    /**
     * Overwrite both entity resource name with `payloadKey`
     *
     * @var string
     */
    protected $payloadKey = 'purchase_order';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    public $publicIdType = 'purchase_order';

    /**
     * These attributes that can be queried
     *
     * @var array
     */
    protected $searchableColumns = [
        'customer_reference_code', 'reference_code'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'public_id',
        'company_uuid',
        'created_by_uuid',
        'supplier_uuid',
        'transaction_uuid',
        'assigned_to_uuid',
        'point_of_contact_uuid',
        'reference_code',
        'reference_url',
        'description',
        'comments',
        'currency',
        'status',
        'meta',
        'order_created_at',
        'expected_delivery_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
        'order_created_at' => 'datetime',
        'expected_delivery_at' => 'datetime',
    ];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['deleted_at'];
}
