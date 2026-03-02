<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Pallet\Traits\HasOperationalAuditTrail;

class CycleCount extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use TracksApiCredential;
    use HasMetaAttributes;
    use HasOperationalAuditTrail;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_cycle_counts';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'cycle_count';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_uuid',
        'warehouse_uuid',
        'zone_uuid',
        'assigned_to_uuid',
        'count_number',
        'type',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'notes',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'         => Json::class,
        'scheduled_at' => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = ['total_items', 'counted_items', 'discrepancies_count', 'accuracy_percentage'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['warehouse', 'zone', 'assignedTo', 'items'];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['count_number', 'status', 'type', 'notes'];

    /**
     * Get the warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid');
    }

    /**
     * Get the zone.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function zone()
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_uuid');
    }

    /**
     * Get the assigned user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedTo()
    {
        return $this->belongsTo(\Fleetbase\Models\User::class, 'assigned_to_uuid');
    }

    /**
     * Get the cycle count items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(CycleCountItem::class, 'cycle_count_uuid');
    }

    /**
     * Get total items count.
     *
     * @return int
     */
    public function getTotalItemsAttribute()
    {
        return $this->items()->count();
    }

    /**
     * Get counted items count.
     *
     * @return int
     */
    public function getCountedItemsAttribute()
    {
        return $this->items()->where('status', 'counted')->count();
    }

    /**
     * Get discrepancies count.
     *
     * @return int
     */
    public function getDiscrepanciesCountAttribute()
    {
        return $this->items()->whereRaw('expected_quantity != counted_quantity')->count();
    }

    /**
     * Get accuracy percentage.
     *
     * @return float
     */
    public function getAccuracyPercentageAttribute()
    {
        if ($this->total_items === 0) {
            return 100;
        }

        $accurate = $this->total_items - $this->discrepancies_count;

        return round(($accurate / $this->total_items) * 100, 2);
    }

    /**
     * Start counting.
     *
     * @return bool
     */
    public function start()
    {
        $this->status = 'in_progress';
        $this->started_at = now();

        return $this->save();
    }

    /**
     * Complete counting.
     *
     * @return bool
     */
    public function complete()
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $result = $this->save();

        // Log operational audit event
        $this->logAuditEvent(
            AuditEventType::CYCLE_COUNT,
            'Cycle Count Completed',
            'completed',
            null,
            [
                'count_number'        => $this->count_number,
                'warehouse_uuid'      => $this->warehouse_uuid,
                'total_items'         => $this->total_items,
                'discrepancies_count' => $this->discrepancies_count,
                'accuracy_percentage' => $this->accuracy_percentage,
            ]
        );

        return $result;
    }

    /**
     * Approve count and apply adjustments.
     *
     * @return bool
     */
    public function approve()
    {
        // Apply inventory adjustments for discrepancies
        foreach ($this->items as $item) {
            if ($item->expected_quantity != $item->counted_quantity) {
                $item->applyAdjustment();
            }
        }

        $this->status = 'approved';
        $result = $this->save();

        // Log operational audit event
        $this->logAuditEvent(
            AuditEventType::CYCLE_COUNT,
            'Cycle Count Approved',
            'approved',
            null,
            [
                'count_number'        => $this->count_number,
                'warehouse_uuid'      => $this->warehouse_uuid,
                'discrepancies_count' => $this->discrepancies_count,
                'accuracy_percentage' => $this->accuracy_percentage,
            ]
        );

        return $result;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->count_number) {
                $model->count_number = 'CC-' . strtoupper(uniqid());
            }
            if (!$model->status) {
                $model->status = 'pending';
            }
            if (!$model->type) {
                $model->type = 'standard'; // standard, full, spot, abc
            }
        });
    }
}
