<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasMetaAttributes;

class WarehouseZone extends Model
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
    protected $table = 'pallet_warehouse_zones';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'zone';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_uuid',
        'warehouse_uuid',
        'name',
        'code',
        'type',
        'status',
        'temperature_controlled',
        'temperature_range',
        'capacity',
        'current_utilization',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'                   => Json::class,
        'temperature_range'      => Json::class,
        'temperature_controlled' => 'boolean',
        'capacity'               => 'decimal:2',
        'current_utilization'    => 'decimal:2',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = ['utilization_percentage'];

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
    protected $searchableColumns = ['name', 'code', 'type', 'status'];

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
     * Get bin locations in this zone.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function binLocations()
    {
        return $this->hasMany(BinLocation::class, 'zone_uuid');
    }

    /**
     * Get aisles in this zone.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function aisles()
    {
        return $this->hasMany(WarehouseAisle::class, 'zone_uuid');
    }

    /**
     * Get utilization percentage.
     *
     * @return float
     */
    public function getUtilizationPercentageAttribute()
    {
        if (!$this->capacity || $this->capacity == 0) {
            return 0;
        }

        return round(($this->current_utilization / $this->capacity) * 100, 2);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->status) {
                $model->status = 'active';
            }
            if (!$model->type) {
                $model->type = 'general'; // general, receiving, shipping, staging, returns, cold_storage
            }
            if (!isset($model->temperature_controlled)) {
                $model->temperature_controlled = false;
            }
            if (!$model->current_utilization) {
                $model->current_utilization = 0;
            }
        });
    }
}
