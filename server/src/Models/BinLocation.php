<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;

class BinLocation extends Model
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
    protected $table = 'pallet_bin_locations';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'bin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_uuid',
        'warehouse_uuid',
        'zone_uuid',
        'aisle_uuid',
        'rack_uuid',
        'section_uuid',
        'bin_number',
        'barcode',
        'type',
        'status',
        'capacity',
        'current_volume',
        'dimensions',
        'is_pickable',
        'is_replenishable',
        'priority',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'              => Json::class,
        'dimensions'        => Json::class,
        'capacity'          => 'decimal:2',
        'current_volume'    => 'decimal:2',
        'priority'          => 'integer',
        'is_pickable'       => 'boolean',
        'is_replenishable'  => 'boolean',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = ['utilization_percentage', 'available_capacity'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    /**
     * Relationships to eager load.
     *
     * `warehouse` is deliberately excluded — it eager-loads its zones, which
     * would pull the whole warehouse graph in for every bin.
     *
     * @var array
     */
    protected $with = ['zone'];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['bin_number', 'barcode', 'type', 'status'];

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
     * Get the aisle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function aisle()
    {
        return $this->belongsTo(WarehouseAisle::class, 'aisle_uuid', 'uuid');
    }

    /**
     * Get the rack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rack()
    {
        return $this->belongsTo(WarehouseRack::class, 'rack_uuid', 'uuid');
    }

    /**
     * Get the section.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function section()
    {
        return $this->belongsTo(WarehouseSection::class, 'section_uuid', 'uuid');
    }

    /**
     * Get inventory items in this bin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventoryItems()
    {
        return $this->hasMany(Inventory::class, 'bin_location_uuid', 'uuid');
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

        return round(($this->current_volume / $this->capacity) * 100, 2);
    }

    /**
     * Get available capacity.
     *
     * @return float
     */
    public function getAvailableCapacityAttribute()
    {
        return max(0, $this->capacity - $this->current_volume);
    }

    /**
     * Check if bin has available capacity.
     *
     * @param float $requiredVolume
     *
     * @return bool
     */
    public function hasCapacity($requiredVolume = 0)
    {
        return $this->available_capacity >= $requiredVolume;
    }

    /**
     * Add volume to bin.
     *
     * @param float $volume
     *
     * @return bool
     */
    public function addVolume($volume)
    {
        $this->current_volume += $volume;

        return $this->save();
    }

    /**
     * Remove volume from bin.
     *
     * @param float $volume
     *
     * @return bool
     */
    public function removeVolume($volume)
    {
        $this->current_volume = max(0, $this->current_volume - $volume);

        return $this->save();
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
                $model->type = 'standard'; // standard, bulk, pallet, shelf
            }
            if (!$model->current_volume) {
                $model->current_volume = 0;
            }
            if (!isset($model->is_pickable)) {
                $model->is_pickable = true;
            }
            if (!isset($model->is_replenishable)) {
                $model->is_replenishable = true;
            }
        });
    }
}
