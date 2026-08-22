<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\FleetOps\Models\Contact;
use Fleetbase\Models\Company;
use Fleetbase\Models\Model;
use Fleetbase\Models\Transaction;
use Fleetbase\Models\User;
use Fleetbase\Pallet\Traits\HasOperationalAuditTrail;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesOrder extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use HasOperationalAuditTrail;
    use LogsActivity;

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
    protected $searchableColumns = ['order_number', 'customer_type', 'status', 'reference_code', 'reference_url', 'description', 'comments'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'public_id',
        'order_number',
        'company_uuid',
        'created_by_uuid',
        'transaction_uuid',
        'assigned_to_uuid',
        'point_of_contact_uuid',
        'customer_uuid',
        'customer_type',
        'supplier_uuid',
        'warehouse_uuid',
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

    public $timestamps = true;

    protected $dates = ['expected_delivery_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => Json::class,
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

    protected $with = ['customer', 'warehouse', 'items.product', 'items.variant', 'items.warehouse', 'items.inventory'];

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

    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_uuid', 'uuid');
    }

    /**
     * Relationship with the supplier associated with the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_uuid', 'uuid');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Relationship: the line items on this Sales Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_uuid', 'uuid')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Returns the total order value (sum of all item total_prices).
     */
    public function getTotalValueAttribute(): float
    {
        return (float) $this->items()->sum('total_price');
    }

    /**
     * Returns the total number of line items.
     */
    public function getItemCountAttribute(): int
    {
        return $this->items()->count();
    }

    /**
     * Mark the sales order as fulfilled and log an operational audit event.
     * This method is called by SalesOrderController::fulfill().
     *
     * @param array $fulfilledItems Array of fulfilled line items with quantities
     */
    public function markAsFulfilled(array $fulfilledItems = []): bool
    {
        $this->status = 'fulfilled';
        $result       = $this->save();

        // Log operational audit event
        $this->logAuditEvent(
            AuditEventType::SO_FULFILLED,
            'Sales Order Fulfilled',
            'fulfilled',
            null,
            [
                'order_number'    => $this->order_number,
                'customer_uuid'   => $this->customer_uuid,
                'supplier_uuid'   => $this->supplier_uuid,
                'fulfilled_items' => $fulfilledItems,
            ]
        );

        return $result;
    }

    /**
     * Mark the sales order as partially fulfilled and log an operational audit event.
     * Partial fulfillment deducts stock, so it belongs in the audit trail just as full
     * fulfillment does; the `type` discriminates them while keeping the same event type
     * so filtering by SO_FULFILLED surfaces the whole fulfillment history.
     *
     * @param array $fulfilledItems Array of fulfilled line items with quantities
     */
    public function markAsPartiallyFulfilled(array $fulfilledItems = []): bool
    {
        $this->status = 'partial';
        $result       = $this->save();

        $this->logAuditEvent(
            AuditEventType::SO_FULFILLED,
            'Sales Order Partially Fulfilled',
            'partially_fulfilled',
            null,
            [
                'order_number'    => $this->order_number,
                'customer_uuid'   => $this->customer_uuid,
                'supplier_uuid'   => $this->supplier_uuid,
                'fulfilled_items' => $fulfilledItems,
            ]
        );

        return $result;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at    = now();
            $model->order_date_at = now();

            // every other numbered record in the module carries its own series —
            // transfers TR-, waves WAVE-, cycle counts CC-, pick lists PL-. Orders
            // had none, so the resource synthesised one from public_id and the
            // detail panel printed the same string in two adjacent fields.
            if (!$model->order_number) {
                $model->order_number = 'SO-' . strtoupper(uniqid());
            }
        });
    }

    /**
     * Configure Spatie activity log options.
     * Logs only the specified attributes when they change (dirty only).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'reference_code',
                'description',
                'currency',
                'expected_delivery_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
