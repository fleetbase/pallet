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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                                                             $id
 * @property string                                                           $uuid
 * @property ?string                                                          $public_id
 * @property ?string                                                          $order_number
 * @property ?string                                                          $company_uuid
 * @property ?string                                                          $created_by_uuid
 * @property ?string                                                          $supplier_uuid
 * @property ?string                                                          $warehouse_uuid
 * @property ?string                                                          $transaction_uuid
 * @property ?string                                                          $assigned_to_uuid
 * @property ?string                                                          $point_of_contact_uuid
 * @property ?string                                                          $reference_code
 * @property ?string                                                          $reference_url
 * @property ?string                                                          $description
 * @property ?string                                                          $comments
 * @property ?string                                                          $currency
 * @property ?string                                                          $status
 * @property ?array                                                           $meta
 * @property ?\Illuminate\Support\Carbon                                      $order_created_at
 * @property ?\Illuminate\Support\Carbon                                      $expected_delivery_at
 * @property ?\Illuminate\Support\Carbon                                      $created_at
 * @property ?\Illuminate\Support\Carbon                                      $updated_at
 * @property ?\Illuminate\Support\Carbon                                      $deleted_at
 * @property \Fleetbase\Pallet\Models\User|null                               $assignedTo
 * @property \Fleetbase\Pallet\Models\Company|null                            $company
 * @property \Fleetbase\Pallet\Models\User|null                               $createdBy
 * @property \Illuminate\Database\Eloquent\Collection<int, PurchaseOrderItem> $items
 * @property \Fleetbase\Pallet\Models\Contact|null                            $pointOfContact
 * @property Supplier|null                                                    $supplier
 * @property \Fleetbase\Pallet\Models\Transaction|null                        $transaction
 * @property Warehouse|null                                                   $warehouse
 */
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
    protected $searchableColumns = ['order_number', 'reference_code', 'reference_url', 'description', 'comments', 'currency', 'status'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'public_id',
        'order_number',
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
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => Json::class,
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array<int, string>
     */
    protected $appends = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * @var array<int, string>
     */
    protected $with = ['supplier', 'warehouse', 'items.product', 'items.variant', 'items.warehouse'];

    /**
     * Relationship with the company associated with the purchase order.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the user who created the purchase order.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the supplier associated with the purchase order.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_uuid', 'uuid');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Relationship: the line items on this Purchase Order.
     */
    public function items(): HasMany
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
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_uuid', 'uuid');
    }

    /**
     * Relationship with the user assigned to the purchase order.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_uuid', 'uuid');
    }

    /**
     * Relationship with the point of contact associated with the purchase order.
     */
    public function pointOfContact(): BelongsTo
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
                'order_number'   => $this->order_number,
                'supplier_uuid'  => $this->supplier_uuid,
                'received_items' => $receivedItems,
            ]
        );

        return $result;
    }

    /**
     * Mark the purchase order as partially received and log an operational audit event.
     * Partial receipts move stock, so they belong in the audit trail just as full
     * receipts do; the `type` discriminates them while keeping the same event type
     * so filtering by PO_RECEIVED surfaces the whole receiving history.
     *
     * @param array $receivedItems Array of received line items with quantities
     */
    public function markAsPartiallyReceived(array $receivedItems = []): bool
    {
        $this->status = 'partial';
        $result       = $this->save();

        $this->logAuditEvent(
            AuditEventType::PO_RECEIVED,
            'Purchase Order Partially Received',
            'partially_received',
            null,
            [
                'order_number'   => $this->order_number,
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

            // every other numbered record in the module carries its own series —
            // transfers TR-, waves WAVE-, cycle counts CC-, pick lists PL-. Orders
            // had none, so the resource synthesised one from public_id and the
            // detail panel printed the same string in two adjacent fields.
            if (!$model->order_number) {
                $model->order_number = 'PO-' . strtoupper(uniqid());
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
