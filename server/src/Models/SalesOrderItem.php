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
 * SalesOrderItem.
 *
 * Represents a single product line on a Sales Order.
 * On SO fulfilment, each item decrements the matching Inventory record in the
 * source warehouse. The `quantity_fulfilled` field is updated during fulfilment
 * and may be less than `quantity` for partial fulfilments.
 *
 * Status lifecycle: pending → partial → fulfilled | cancelled
 *
 * @property ?int                        $id
 * @property string                      $uuid
 * @property ?string                     $public_id
 * @property ?string                     $company_uuid
 * @property ?string                     $sales_order_uuid
 * @property ?string                     $created_by_uuid
 * @property ?string                     $product_uuid
 * @property ?string                     $variant_uuid
 * @property ?string                     $warehouse_uuid
 * @property ?string                     $inventory_uuid
 * @property ?int                        $quantity
 * @property ?int                        $quantity_fulfilled
 * @property ?string                     $currency
 * @property ?string                     $unit_price
 * @property ?string                     $total_price
 * @property ?string                     $unit_of_measure
 * @property ?string                     $sku
 * @property ?string                     $lot_number
 * @property ?string                     $serial_number
 * @property ?string                     $status
 * @property ?string                     $notes
 * @property ?array                      $meta
 * @property ?\Illuminate\Support\Carbon $fulfilled_at
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property Inventory|null              $inventory
 * @property Product|null                $product
 * @property SalesOrder|null             $salesOrder
 * @property ProductVariant|null         $variant
 * @property Warehouse|null              $warehouse
 */
class SalesOrderItem extends Model
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
    protected $table = 'pallet_sales_order_items';

    /**
     * The type of public_id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'soi';

    /**
     * The payload key used when the model is sent/received via API.
     *
     * @var string
     */
    protected $payloadKey = 'sales_order_item';

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
        'sales_order_uuid',
        'created_by_uuid',
        'product_uuid',
        'variant_uuid',
        'warehouse_uuid',
        'inventory_uuid',
        'quantity',
        'quantity_fulfilled',
        'currency',
        'unit_price',
        'total_price',
        'unit_of_measure',
        'sku',
        'lot_number',
        'serial_number',
        'status',
        'notes',
        'meta',
        'fulfilled_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta'               => Json::class,
        'fulfilled_at'       => 'datetime',
        'quantity'           => 'integer',
        'quantity_fulfilled' => 'integer',
        'unit_price'         => 'decimal:4',
        'total_price'        => 'decimal:4',
    ];

    /**
     * Relationship: the parent Sales Order.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_uuid', 'uuid');
    }

    /**
     * Relationship: the Product being sold.
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
     * Relationship: the source Warehouse.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Relationship: the specific Inventory record being drawn from.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_uuid', 'uuid');
    }

    /**
     * Scope: only pending items.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: only fulfilled items.
     */
    public function scopeFulfilled($query)
    {
        return $query->where('status', 'fulfilled');
    }

    /**
     * Returns true if this item has been fully fulfilled.
     */
    public function isFullyFulfilled(): bool
    {
        return $this->quantity_fulfilled >= $this->quantity;
    }

    /**
     * Returns the outstanding (unfulfilled) quantity.
     */
    public function getOutstandingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->quantity_fulfilled);
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
            ->logOnly(['quantity', 'quantity_fulfilled', 'unit_price', 'status', 'lot_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
