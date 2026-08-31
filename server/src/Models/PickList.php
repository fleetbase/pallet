<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                                                        $id
 * @property string                                                      $uuid
 * @property ?string                                                     $public_id
 * @property ?string                                                     $company_uuid
 * @property ?string                                                     $warehouse_uuid
 * @property ?string                                                     $sales_order_uuid
 * @property ?string                                                     $wave_uuid
 * @property ?string                                                     $assigned_to_uuid
 * @property ?string                                                     $pick_list_number
 * @property ?string                                                     $type
 * @property ?int                                                        $priority
 * @property ?string                                                     $status
 * @property ?\Illuminate\Support\Carbon                                 $started_at
 * @property ?\Illuminate\Support\Carbon                                 $completed_at
 * @property ?string                                                     $notes
 * @property ?array                                                      $meta
 * @property ?\Illuminate\Support\Carbon                                 $created_at
 * @property ?\Illuminate\Support\Carbon                                 $updated_at
 * @property ?\Illuminate\Support\Carbon                                 $deleted_at
 * @property \Fleetbase\Models\User|null                                 $assignedTo
 * @property \Illuminate\Database\Eloquent\Collection<int, PickListItem> $items
 * @property SalesOrder|null                                             $salesOrder
 * @property Warehouse|null                                              $warehouse
 * @property Wave|null                                                   $wave
 * @property mixed                                                       $total_items
 * @property mixed                                                       $picked_items
 * @property mixed                                                       $completion_percentage
 */
class PickList extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use TracksApiCredential;
    use HasMetaAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_pick_lists';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'pick_list';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'pick_list';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_uuid',
        'warehouse_uuid',
        'sales_order_uuid',
        'wave_uuid',
        'assigned_to_uuid',
        'pick_list_number',
        'type',
        'priority',
        'status',
        'started_at',
        'completed_at',
        'notes',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta'         => Json::class,
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'priority'     => 'integer',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array<int, string>
     */
    protected $appends = ['total_items', 'picked_items', 'completion_percentage'];

    /**
     * Relationships to eager load.
     *
     * @var array<int, string>
     */
    protected $with = ['warehouse', 'salesOrder', 'wave', 'assignedTo', 'items'];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['pick_list_number', 'status', 'type', 'notes'];

    /**
     * Get the warehouse.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get the sales order.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_uuid', 'uuid');
    }

    /**
     * Get the wave.
     */
    public function wave(): BelongsTo
    {
        return $this->belongsTo(Wave::class, 'wave_uuid', 'uuid');
    }

    /**
     * Get the assigned user.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\Fleetbase\Models\User::class, 'assigned_to_uuid', 'uuid');
    }

    /**
     * Get the pick list items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PickListItem::class, 'pick_list_uuid', 'uuid');
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
     * Get picked items count.
     *
     * @return int
     */
    public function getPickedItemsAttribute()
    {
        return $this->items()->where('status', 'picked')->count();
    }

    /**
     * Get completion percentage.
     *
     * @return float
     */
    public function getCompletionPercentageAttribute()
    {
        if ($this->total_items === 0) {
            return 0;
        }

        return round(($this->picked_items / $this->total_items) * 100, 2);
    }

    /**
     * Start picking.
     *
     * @return bool
     */
    public function start()
    {
        if (!in_array($this->status, ['pending', 'assigned'], true)) {
            throw new \RuntimeException('Only pending or assigned pick lists can be started.');
        }

        if ($this->items()->count() === 0) {
            throw new \RuntimeException('Pick list cannot be started without pick items.');
        }

        $this->status     = 'in_progress';
        $this->started_at = now();

        return $this->save();
    }

    /**
     * Complete picking.
     *
     * @return bool
     */
    public function complete()
    {
        if ($this->status !== 'in_progress') {
            throw new \RuntimeException('Only in-progress pick lists can be completed.');
        }

        if ($this->items()->count() === 0) {
            throw new \RuntimeException('Pick list cannot be completed without pick items.');
        }

        if ($this->items()->where('status', '!=', 'picked')->exists()) {
            throw new \RuntimeException('All pick list items must be picked before completing the pick list.');
        }

        $this->status       = 'completed';
        $this->completed_at = now();

        return $this->save();
    }

    /**
     * Assign to user.
     *
     * @param string $userUuid
     *
     * @return bool
     */
    public function assignTo($userUuid)
    {
        if (!$userUuid) {
            throw new \RuntimeException('A user must be selected before assigning a pick list.');
        }

        if (!in_array($this->status, ['pending', 'assigned'], true)) {
            throw new \RuntimeException('Only pending pick lists can be assigned.');
        }

        $this->assigned_to_uuid = $userUuid;
        $this->status           = 'assigned';

        return $this->save();
    }

    /**
     * Scope to get pending pick lists.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get in progress pick lists.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pick_list_number) {
                $model->pick_list_number = 'PL-' . strtoupper(uniqid());
            }
            if (!$model->status) {
                $model->status = 'pending';
            }
            if (!$model->type) {
                $model->type = 'discrete'; // discrete, batch, zone, wave
            }
            if (!$model->priority) {
                $model->priority = 5; // 1-10 scale
            }
        });
    }
}
