<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use RuntimeException;

class Wave extends Model
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
    protected $table = 'pallet_waves';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'wave';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_uuid',
        'warehouse_uuid',
        'wave_number',
        'type',
        'status',
        'priority',
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
        'priority'     => 'integer',
        'scheduled_at' => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = ['total_pick_lists', 'completed_pick_lists'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['warehouse'];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['wave_number', 'status', 'type', 'notes'];

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
     * Get the pick lists in this wave.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pickLists()
    {
        return $this->hasMany(PickList::class, 'wave_uuid', 'uuid');
    }

    /**
     * Get total pick lists count.
     *
     * @return int
     */
    public function getTotalPickListsAttribute()
    {
        return $this->pickLists()->count();
    }

    /**
     * Get completed pick lists count.
     *
     * @return int
     */
    public function getCompletedPickListsAttribute()
    {
        return $this->pickLists()->where('status', 'completed')->count();
    }

    /**
     * Start the wave.
     *
     * @return bool
     */
    public function start()
    {
        if (!in_array($this->status, ['pending', 'released'], true)) {
            throw new RuntimeException('Only pending or released waves can be started.');
        }

        $this->status     = 'in_progress';
        $this->started_at = now();

        return $this->save();
    }

    /**
     * Complete the wave.
     *
     * @return bool
     */
    public function complete()
    {
        if ($this->status !== 'in_progress') {
            throw new RuntimeException('Only in-progress waves can be completed.');
        }

        $this->status       = 'completed';
        $this->completed_at = now();

        return $this->save();
    }

    /**
     * Release the wave (create pick lists).
     *
     * @return bool
     */
    public function release()
    {
        if ($this->status !== 'pending') {
            throw new RuntimeException('Only pending waves can be released.');
        }

        $this->status = 'released';

        return $this->save();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->wave_number) {
                $model->wave_number = 'WAVE-' . strtoupper(uniqid());
            }
            if (!$model->status) {
                $model->status = 'pending';
            }
            if (!$model->type) {
                $model->type = 'standard'; // standard, express, bulk
            }
            if (!$model->priority) {
                $model->priority = 5;
            }
        });
    }
}
