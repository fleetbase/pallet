<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Models\User;
use Fleetbase\Pallet\Traits\HasOperationalAuditTrail;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
     * @var array
     */
    protected $fillable = [
        'uuid',
        'public_id',
        'company_uuid',
        'created_by_uuid',
        'product_uuid',
        'variant_uuid',
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
     * @var array
     */
    protected $casts = [
        'meta'              => Json::class,
        'approval_required' => 'boolean',
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
     * Relationship with the company associated with the stock adjustment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(\Fleetbase\Models\Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the user who created the stock adjustment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the product associated with the stock adjustment.
     *
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

    public function user()
    {
        return $this->belongsTo(User::class, 'assignee_uuid');
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
