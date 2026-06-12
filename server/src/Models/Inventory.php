<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Spatie\Activitylog\LogOptions;

class Inventory extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use HasMetaAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_inventories';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'inventory';

    /**
     * The type of `public_id` to generate.
     *
     * @var string
     */
    protected $publicIdType = 'inventory';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['product.name', 'variant.name', 'variant.sku', 'warehouse.address', 'comments', 'lot_number', 'serial_number'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'supplier',
        'supplier_uuid',
        'company_uuid',
        'created_by_uuid',
        'product_uuid',
        'variant_uuid',
        'warehouse_uuid',
        'batch_uuid',
        'bin_location_uuid',
        'zone_uuid',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'min_quantity',
        'max_quantity',
        'reorder_point',
        'lot_number',
        'serial_number',
        'uom',
        'unit_cost',
        'manufactured_date_at',
        'expiry_date_at',
        'received_at',
        'last_counted_at',
        'comments',
        'status',
        'meta',
    ];

    public $timestamps = true;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'                => Json::class,
        'quantity'            => 'integer',
        'reserved_quantity'   => 'integer',
        'available_quantity'  => 'integer',
        'min_quantity'        => 'integer',
        'max_quantity'        => 'integer',
        'reorder_point'       => 'integer',
        'unit_cost'           => 'decimal:2',
        'expiry_date_at'      => 'datetime',
        'manufactured_date_at'=> 'datetime',
        'received_at'         => 'datetime',
        'last_counted_at'     => 'datetime',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = [
        'incrementing_id',
        'is_low_stock',
        'is_out_of_stock',
        'is_expired',
        'is_expiring_soon',
        'days_until_expiry',
        'total_value',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    protected $with = ['product', 'variant', 'batch', 'warehouse', 'supplier', 'binLocation'];

    protected $filterParams = [
        'comments',
        'expiry_date_at',
        'status',
        'company',
        'createdBy',
        'lot_number',
        'serial_number',
        'warehouse',
        'product',
        'variant',
    ];

    public function getIncrementingIdAttribute(): ?int
    {
        return static::select('id')->where('uuid', $this->uuid)->value('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'uuid');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_uuid', 'uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_uuid', 'uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_uuid', 'uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function binLocation()
    {
        return $this->belongsTo(BinLocation::class, 'bin_location_uuid', 'uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function zone()
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_uuid', 'uuid');
    }

    /**
     * Get inventory reservations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reservations()
    {
        return $this->hasMany(InventoryReservation::class, 'inventory_uuid', 'uuid');
    }

    /**
     * Get stock transactions for this inventory.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'inventory_uuid', 'uuid');
    }

    /**
     * Check if stock is low.
     *
     * @return bool
     */
    public function getIsLowStockAttribute()
    {
        return $this->available_quantity <= $this->min_quantity;
    }

    /**
     * Check if out of stock.
     *
     * @return bool
     */
    public function getIsOutOfStockAttribute()
    {
        return $this->available_quantity <= 0;
    }

    /**
     * Check if expired.
     *
     * @return bool
     */
    public function getIsExpiredAttribute()
    {
        return $this->expiry_date_at && $this->expiry_date_at->isPast();
    }

    /**
     * Check if expiring soon (within 30 days).
     *
     * @return bool
     */
    public function getIsExpiringSoonAttribute()
    {
        if (!$this->expiry_date_at) {
            return false;
        }

        return $this->expiry_date_at->isFuture() && $this->expiry_date_at->diffInDays(now()) <= 30;
    }

    /**
     * Get days until expiry.
     *
     * @return int|null
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expiry_date_at) {
            return null;
        }

        if ($this->is_expired) {
            return 0;
        }

        return $this->expiry_date_at->diffInDays(now());
    }

    /**
     * Get total inventory value.
     *
     * @return float
     */
    public function getTotalValueAttribute()
    {
        return $this->quantity * ($this->unit_cost ?? $this->product->unit_cost ?? 0);
    }

    /**
     * Reserve quantity.
     *
     * @param int $quantity
     *
     * @return bool
     */
    public function reserve($quantity)
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->reserved_quantity += $quantity;
        $this->available_quantity -= $quantity;

        return $this->save();
    }

    /**
     * Release reservation.
     *
     * @param int $quantity
     *
     * @return bool
     */
    public function releaseReservation($quantity)
    {
        $this->reserved_quantity  = max(0, $this->reserved_quantity - $quantity);
        $this->available_quantity = $this->quantity - $this->reserved_quantity;

        return $this->save();
    }

    /**
     * Deduct quantity (for picks/shipments).
     *
     * @param int $quantity
     *
     * @return bool
     */
    public function deduct($quantity)
    {
        $this->quantity -= $quantity;
        $this->available_quantity = $this->quantity - $this->reserved_quantity;

        return $this->save();
    }

    /**
     * Add quantity (for receiving/adjustments).
     *
     * @param int $quantity
     *
     * @return bool
     */
    public function add($quantity)
    {
        $this->quantity += $quantity;
        $this->available_quantity = $this->quantity - $this->reserved_quantity;

        return $this->save();
    }

    /**
     * Undocumented function.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSummarizeByProduct($query)
    {
        return $query
            ->selectRaw('
                pallet_inventories.product_uuid,
                pallet_inventories.variant_uuid,
                pallet_inventories.batch_uuid,
                pallet_inventories.supplier_uuid,
                pallet_inventories.warehouse_uuid,
                MAX(pallet_inventories.created_at) as latest_created_at,
                MAX(pallet_inventories.updated_at) as latest_updated_at,
                MAX(pallet_inventories.public_id) as latest_public_id,
                MAX(pallet_inventories.uuid) as latest_uuid,
                MAX(pallet_inventories.comments) as latest_comments,
                (SELECT GROUP_CONCAT(DISTINCT pallet_batches.uuid) FROM pallet_batches WHERE pallet_batches.uuid = pallet_inventories.batch_uuid) as batch_uuids,
                (SELECT GROUP_CONCAT(DISTINCT pallet_batches.batch_number) FROM pallet_batches WHERE pallet_batches.uuid = pallet_inventories.batch_uuid) as batch_numbers,
                SUM(pallet_inventories.quantity) as total_quantity,
                SUM(pallet_inventories.available_quantity) as total_available_quantity,
                MAX(pallet_inventories.min_quantity) as minimum_quantity,
                MAX(pallet_inventories.expiry_date_at) as latest_expiry_date_at
            ')
            ->leftJoin('pallet_batches', 'pallet_inventories.batch_uuid', '=', 'pallet_batches.uuid')
            ->groupBy('pallet_inventories.product_uuid', 'pallet_inventories.variant_uuid', 'pallet_inventories.batch_uuid', 'pallet_inventories.supplier_uuid', 'pallet_inventories.warehouse_uuid');
    }

    /**
     * Scope to get low stock items.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('available_quantity <= min_quantity');
    }

    /**
     * Scope to get expired items.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date_at')
            ->where('expiry_date_at', '<', now());
    }

    /**
     * Scope to get expiring soon items.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int                                   $days
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date_at')
            ->where('expiry_date_at', '>', now())
            ->where('expiry_date_at', '<=', now()->addDays($days));
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();
            if (!$model->received_at) {
                $model->received_at = now();
            }
            if (!isset($model->reserved_quantity)) {
                $model->reserved_quantity = 0;
            }
            if (!isset($model->available_quantity)) {
                $model->available_quantity = $model->quantity ?? 0;
            }
        });

        static::saving(function ($model) {
            // Ensure available quantity is calculated correctly
            if ($model->isDirty(['quantity', 'reserved_quantity'])) {
                $model->available_quantity = max(0, $model->quantity - $model->reserved_quantity);
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
                'quantity',
                'unit_price',
                'status',
                'warehouse_uuid',
                'product_uuid',
                'variant_uuid',
                'batch_uuid',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
