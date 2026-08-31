<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                                                          $id
 * @property string                                                        $uuid
 * @property ?string                                                       $public_id
 * @property ?string                                                       $company_uuid
 * @property ?string                                                       $warehouse_uuid
 * @property ?string                                                       $name
 * @property ?string                                                       $code
 * @property ?string                                                       $type
 * @property ?string                                                       $status
 * @property ?bool                                                         $temperature_controlled
 * @property ?array                                                        $temperature_range
 * @property ?string                                                       $capacity
 * @property ?string                                                       $current_utilization
 * @property ?array                                                        $meta
 * @property ?\Illuminate\Support\Carbon                                   $created_at
 * @property ?\Illuminate\Support\Carbon                                   $updated_at
 * @property ?\Illuminate\Support\Carbon                                   $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<int, WarehouseAisle> $aisles
 * @property \Illuminate\Database\Eloquent\Collection<int, BinLocation>    $binLocations
 * @property Warehouse|null                                                $warehouse
 * @property mixed                                                         $utilization_percentage
 */
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
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'warehouse_zone';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'zone';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
     * @var array<string, string>
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
     * @var array<int, string>
     */
    protected $appends = ['utilization_percentage'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    /**
     * Relationships to eager load.
     *
     * Deliberately does NOT include `warehouse`: Warehouse eager-loads its
     * zones, so a back-reference here recurses until memory is exhausted.
     *
     * @var array<int, string>
     */
    protected $with = [];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['name', 'code', 'type', 'status'];

    /**
     * Get the warehouse.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get bin locations in this zone.
     */
    public function binLocations(): HasMany
    {
        return $this->hasMany(BinLocation::class, 'zone_uuid', 'uuid');
    }

    /**
     * Get aisles in this zone.
     */
    public function aisles(): HasMany
    {
        return $this->hasMany(WarehouseAisle::class, 'zone_uuid', 'uuid');
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
