<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Casts\Json;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * PurchaseOrderItem
 *
 * Represents a single product line on a Purchase Order.
 * On PO receipt, each item creates or increments an Inventory record in the
 * target warehouse. The `quantity_received` field is updated during receipt
 * and may be less than `quantity` for partial receipts.
 *
 * Status lifecycle: pending → partial → received | cancelled
 */
class PurchaseOrderItem extends Model
{
    use HasUuid;
    use HasPublicId;
    use TracksApiCredential;
    use SoftDeletes;
    use LogsActivity;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_purchase_order_items';

    /**
     * The type of public_id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'poi';

    /**
     * The payload key used when the model is sent/received via API.
     *
     * @var string
     */
    protected $payloadKey = 'purchase_order_item';

    /**
     * Columns that can be searched.
     *
     * @var array
     */
    protected $searchableColumns = ['sku', 'lot_number', 'serial_number', 'status'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'public_id',
        'company_uuid',
        'purchase_order_uuid',
        'created_by_uuid',
        'product_uuid',
        'warehouse_uuid',
        'quantity',
        'quantity_received',
        'currency',
        'unit_price',
        'unit_cost',
        'total_price',
        'unit_of_measure',
        'sku',
        'lot_number',
        'serial_number',
        'expiry_date',
        'status',
        'notes',
        'meta',
        'received_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'        => Json::class,
        'expiry_date' => 'date',
        'received_at' => 'datetime',
        'quantity'    => 'integer',
        'quantity_received' => 'integer',
        'unit_price'  => 'decimal:4',
        'unit_cost'   => 'decimal:4',
        'total_price' => 'decimal:4',
    ];

    /**
     * Relationship: the parent Purchase Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_uuid', 'uuid');
    }

    /**
     * Relationship: the Product being ordered.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'uuid');
    }

    /**
     * Relationship: the destination Warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Scope: only pending items.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: only received items.
     */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Returns true if this item has been fully received.
     *
     * @return bool
     */
    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity;
    }

    /**
     * Returns the outstanding (unreceived) quantity.
     *
     * @return int
     */
    public function getOutstandingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->quantity_received);
    }

    /**
     * Recalculate and persist the total_price based on unit_price × quantity.
     *
     * @return void
     */
    public function recalculateTotalPrice(): void
    {
        if ($this->unit_price !== null && $this->quantity !== null) {
            $this->total_price = round($this->unit_price * $this->quantity, 4);
        }
    }

    /**
     * Configure Spatie activity log options.
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity', 'quantity_received', 'unit_price', 'status', 'lot_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
