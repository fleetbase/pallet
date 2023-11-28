<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasUuid;

class SalesOrder extends Model
{
    use HasUuid;
    use HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_sales_orders';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'sales_order';

    /**
     * The type of `public_id` to generate.
     *
     * @var string
     */
    protected $publicIdType = 'sales_order';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['uuid', 'public_id', 'company_uuid', 'created_by_uuid', 'transaction_uuid', 'assigned_to_uuid', 'point_of_contact_uuid', 'customer_uuid', 'customer_type', 'status', 'customer_reference_code', 'reference_code', 'reference_url', 'description', 'comments', 'order_date_at', 'expected_delivery_at', 'created_at'];

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
        'transaction_uuid',
        'assigned_to_uuid',
        'point_of_contact_uuid',
        'customer_uuid',
        'customer_type',
        'meta',
        'status',
        'customer_reference_code',
        'reference_code',
        'reference_url',
        'description',
        'comments',
        'order_date_at',
        'expected_delivery_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'json',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Relationship with the company associated with the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the user who created the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the transaction associated with the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_uuid', 'uuid');
    }

    /**
     * Relationship with the user assigned to the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_uuid', 'uuid');
    }

    /**
     * Relationship with the point of contact associated with the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pointOfContact()
    {
        return $this->belongsTo(Contact::class, 'point_of_contact_uuid', 'uuid');
    }

    /**
     * Relationship with the customer associated with the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_uuid', 'uuid');
    }
}
