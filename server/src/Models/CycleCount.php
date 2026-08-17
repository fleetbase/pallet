<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Pallet\Traits\HasOperationalAuditTrail;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Illuminate\Support\Facades\DB;

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
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get the zone.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function zone()
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_uuid', 'uuid');
    }

    /**
     * Get the assigned user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedTo()
    {
        return $this->belongsTo(\Fleetbase\Models\User::class, 'assigned_to_uuid', 'uuid');
    }

    /**
     * Get the cycle count items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(CycleCountItem::class, 'cycle_count_uuid', 'uuid');
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
        if ($this->status !== 'pending') {
            throw new \RuntimeException('Only pending cycle counts can be started.');
        }

        return DB::transaction(function () {
            if ($this->items()->count() === 0) {
                $this->seedItemsFromInventory();
            }

            $this->status     = 'in_progress';
            $this->started_at = now();

            return $this->save();
        });
    }

    /**
     * Complete counting.
     *
     * @return bool
     */
    public function complete()
    {
        if ($this->status !== 'in_progress') {
            throw new \RuntimeException('Only in-progress cycle counts can be completed.');
        }

        if ($this->items()->count() === 0) {
            throw new \RuntimeException('Cycle count cannot be completed without count items.');
        }

        if ($this->items()->where('status', '!=', 'counted')->exists()) {
            throw new \RuntimeException('All cycle count items must be counted before completing the cycle count.');
        }

        $this->status       = 'completed';
        $this->completed_at = now();
        $result             = $this->save();

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

    protected function seedItemsFromInventory(): void
    {
        $inventories = Inventory::where('company_uuid', $this->company_uuid)
            ->where('warehouse_uuid', $this->warehouse_uuid)
            ->when($this->zone_uuid, fn ($query) => $query->where('zone_uuid', $this->zone_uuid))
            ->whereIn('status', ['active', 'available'])
            ->orderBy('product_uuid')
            ->orderBy('variant_uuid')
            ->lockForUpdate()
            ->get();

        if ($inventories->isEmpty()) {
            throw new \RuntimeException('No inventory records were found for this cycle count scope.');
        }

        foreach ($inventories as $inventory) {
            CycleCountItem::create([
                'company_uuid'      => $this->company_uuid,
                'cycle_count_uuid'  => $this->uuid,
                'product_uuid'      => $inventory->product_uuid,
                'variant_uuid'      => $inventory->variant_uuid,
                'inventory_uuid'    => $inventory->uuid,
                'bin_location_uuid' => $inventory->bin_location_uuid,
                'expected_quantity' => (int) $inventory->quantity,
                'counted_quantity'  => 0,
                'variance'          => 0 - (int) $inventory->quantity,
                'status'            => 'pending',
                'lot_number'        => $inventory->lot_number,
                'serial_number'     => $inventory->serial_number,
            ]);
        }
    }

    /**
     * Approve count and apply adjustments.
     *
     * @return bool
     */
    public function approve()
    {
        if ($this->status !== 'completed') {
            throw new \RuntimeException('Only completed cycle counts can be approved.');
        }

        // apply every discrepancy adjustment and the status change atomically —
        // a failing item must not leave the count half-applied
        $result = DB::transaction(function () {
            foreach ($this->items as $item) {
                if ($item->expected_quantity != $item->counted_quantity) {
                    $item->applyAdjustment();
                }
            }

            $this->status = 'approved';

            return $this->save();
        });

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
