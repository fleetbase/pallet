<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;

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
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'pick_list';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
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
     * @var array
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
     * @var array
     */
    protected $appends = ['total_items', 'picked_items', 'completion_percentage'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['warehouse', 'salesOrder', 'assignedTo', 'items'];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['pick_list_number', 'status', 'type', 'notes'];

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
     * Get the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_uuid');
    }

    /**
     * Get the wave.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wave()
    {
        return $this->belongsTo(Wave::class, 'wave_uuid');
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
     * Get the pick list items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(PickListItem::class, 'pick_list_uuid');
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
