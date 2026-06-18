<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
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

class PurchaseOrder extends Model
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
    protected $table = 'pallet_purchase_orders';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'purchase_order';

    /**
     * The type of `public_id` to generate.
     *
     * @var string
     */
    protected $publicIdType = 'purchase_order';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['reference_code', 'reference_url', 'description', 'comments', 'currency', 'status'];

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
        'warehouse_uuid',
        'transaction_uuid',
        'assigned_to_uuid',
        'point_of_contact_uuid',
        'reference_code',
        'reference_url',
        'description',
        'comments',
        'currency',
        'meta',
        'status',
        'order_created_at',
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

    protected $with = ['supplier', 'warehouse', 'items.product', 'items.variant', 'items.warehouse'];

    /**
     * Relationship with the company associated with the purchase order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the user who created the purchase order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the supplier associated with the purchase order.
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
     * Relationship: the line items on this Purchase Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_uuid', 'uuid')
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
     * Relationship with the transaction associated with the purchase order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_uuid', 'uuid');
    }

    /**
     * Relationship with the user assigned to the purchase order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_uuid', 'uuid');
    }

    /**
     * Relationship with the point of contact associated with the purchase order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pointOfContact()
    {
        return $this->belongsTo(Contact::class, 'point_of_contact_uuid', 'uuid');
    }

    /**
     * Mark the purchase order as received and log an operational audit event.
     * This method is called by PurchaseOrderController::receive().
     *
     * @param array $receivedItems Array of received line items with quantities
     */
    public function markAsReceived(array $receivedItems = []): bool
    {
        $this->status = 'received';
        $result       = $this->save();

        // Log operational audit event
        $this->logAuditEvent(
            AuditEventType::PO_RECEIVED,
            'Purchase Order Received',
            'received',
            null,
            [
                'order_number'   => $this->public_id,
                'supplier_uuid'  => $this->supplier_uuid,
                'received_items' => $receivedItems,
            ]
        );

        return $result;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at       = now();
            $model->order_created_at = now();
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
