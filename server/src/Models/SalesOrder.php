<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\Searchable;
use Fleetbase\Traits\SendsWebhooks;
use Fleetbase\Models\Model;

class SalesOrder extends Model
{
    use HasUuid;
    use HasApiModelBehavior;
    use HasPublicId;
    use SendsWebhooks;
    use HasMetaAttributes;
    use Searchable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_sales_orders';

    /**
     * The singularName overwrite.
     *
     * @var string
     */
    protected $singularName = 'sales-order';

    /**
     * Overwrite both entity resource name with `payloadKey`
     *
     * @var string
     */
    protected $payloadKey = 'sales_order';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    public $publicIdType = 'sales_order';

    /**
     * These attributes that can be queried
     *
     * @var array
     */
    protected $searchableColumns = ['customer_reference_code', 'reference_code', 'status'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'customer_reference_code',
        'reference_code',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
        'order_date_at' => 'datetime',
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
