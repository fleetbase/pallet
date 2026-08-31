<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * PurchaseOrderItem.
 *
 * Represents a single product line on a Purchase Order.
 * On PO receipt, each item creates or increments an Inventory record in the
 * target warehouse. The `quantity_received` field is updated during receipt
 * and may be less than `quantity` for partial receipts.
 *
 * Status lifecycle: pending → partial → received | cancelled
 *
 * @property ?int                        $id
 * @property string                      $uuid
 * @property ?string                     $public_id
 * @property ?string                     $company_uuid
 * @property ?string                     $purchase_order_uuid
 * @property ?string                     $created_by_uuid
 * @property ?string                     $product_uuid
 * @property ?string                     $variant_uuid
 * @property ?string                     $warehouse_uuid
 * @property ?int                        $quantity
 * @property ?int                        $quantity_received
 * @property ?string                     $currency
 * @property ?string                     $unit_price
 * @property ?string                     $unit_cost
 * @property ?string                     $total_price
 * @property ?string                     $unit_of_measure
 * @property ?string                     $sku
 * @property ?string                     $lot_number
 * @property ?string                     $serial_number
 * @property ?\Illuminate\Support\Carbon $expiry_date
 * @property ?string                     $status
 * @property ?string                     $notes
 * @property ?array                      $meta
 * @property ?\Illuminate\Support\Carbon $received_at
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property Product|null                $product
 * @property PurchaseOrder|null          $purchaseOrder
 * @property ProductVariant|null         $variant
 * @property Warehouse|null              $warehouse
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
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'public_id',
        'company_uuid',
        'purchase_order_uuid',
        'created_by_uuid',
        'product_uuid',
        'variant_uuid',
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
     * @var array<string, string>
     */
    protected $casts = [
        'meta'              => Json::class,
        'expiry_date'       => 'date',
        'received_at'       => 'datetime',
        'quantity'          => 'integer',
        'quantity_received' => 'integer',
        'unit_price'        => 'decimal:4',
        'unit_cost'         => 'decimal:4',
        'total_price'       => 'decimal:4',
    ];

    /**
     * Relationship: the parent Purchase Order.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_uuid', 'uuid');
    }

    /**
     * Relationship: the Product being ordered.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_uuid', 'uuid');
    }

    /**
     * Relationship: the destination Warehouse.
     */
    public function warehouse(): BelongsTo
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
     */
    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity;
    }

    /**
     * Returns the outstanding (unreceived) quantity.
     */
    public function getOutstandingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->quantity_received);
    }

    /**
     * Recalculate and persist the total_price based on unit_price × quantity.
     */
    public function recalculateTotalPrice(): void
    {
        if ($this->unit_price !== null && $this->quantity !== null) {
            $this->total_price = round($this->unit_price * $this->quantity, 4);
        }
    }

    /**
     * Configure Spatie activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity', 'quantity_received', 'unit_price', 'status', 'lot_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
