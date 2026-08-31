<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Models\User;
use Fleetbase\Pallet\Traits\HasOperationalAuditTrail;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                               $id
 * @property string                             $uuid
 * @property ?string                            $public_id
 * @property ?string                            $company_uuid
 * @property ?string                            $created_by_uuid
 * @property ?string                            $product_uuid
 * @property ?string                            $variant_uuid
 * @property ?string                            $inventory_uuid
 * @property ?string                            $warehouse_uuid
 * @property ?string                            $assignee_uuid
 * @property ?array                             $meta
 * @property ?string                            $type
 * @property ?string                            $reason
 * @property ?bool                              $approval_required
 * @property ?int                               $before_quantity
 * @property ?int                               $after_quantity
 * @property ?int                               $quantity
 * @property ?\Illuminate\Support\Carbon        $created_at
 * @property ?\Illuminate\Support\Carbon        $updated_at
 * @property ?\Illuminate\Support\Carbon        $deleted_at
 * @property \Fleetbase\Models\Company|null     $company
 * @property \Fleetbase\Pallet\Models\User|null $createdBy
 * @property Inventory|null                     $inventory
 * @property Product|null                       $product
 * @property \Fleetbase\Pallet\Models\User|null $user
 * @property ProductVariant|null                $variant
 * @property Warehouse|null                     $warehouse
 */
class StockAdjustment extends Model
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
    protected $table = 'pallet_stock_adjustment';

    /**
     * The singularName overwrite.
     *
     * @var string
     */
    protected $singularName = 'stock_adjustment';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'stock_adjustment';

    /**
     * The type of `public_id` to generate.
     *
     * @var string
     */
    protected $publicIdType = 'stock_adjustment';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['reason'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'public_id',
        'company_uuid',
        'created_by_uuid',
        'product_uuid',
        'variant_uuid',
        'inventory_uuid',
        'warehouse_uuid',
        'assignee_uuid',
        'type',
        'reason',
        'approval_required',
        'before_quantity',
        'after_quantity',
        'quantity',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta'              => Json::class,
        'approval_required' => 'boolean',
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
     * Relationship with the company associated with the stock adjustment.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\Fleetbase\Models\Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the user who created the stock adjustment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the product associated with the stock adjustment.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_uuid', 'uuid');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_uuid', 'uuid');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_uuid', 'uuid');
    }

    /**
     * Boot the model.
     * Automatically logs an operational audit event when a stock adjustment is created.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function (StockAdjustment $adjustment) {
            $adjustment->logAuditEvent(
                AuditEventType::STOCK_ADJUSTMENT,
                'Stock Adjusted',
                $adjustment->type,
                $adjustment->reason,
                [
                    'product_uuid'    => $adjustment->product_uuid,
                    'variant_uuid'    => $adjustment->variant_uuid,
                    'inventory_uuid'  => $adjustment->inventory_uuid,
                    'warehouse_uuid'  => $adjustment->warehouse_uuid,
                    'before_quantity' => $adjustment->before_quantity,
                    'after_quantity'  => $adjustment->after_quantity,
                    'quantity_delta'  => $adjustment->quantity,
                ]
            );
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
                'type',
                'reason',
                'quantity',
                'before_quantity',
                'after_quantity',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
