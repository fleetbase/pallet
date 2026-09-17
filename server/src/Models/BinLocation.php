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
 * @property ?int                                                     $id
 * @property string                                                   $uuid
 * @property ?string                                                  $public_id
 * @property ?string                                                  $company_uuid
 * @property ?string                                                  $warehouse_uuid
 * @property ?string                                                  $zone_uuid
 * @property ?string                                                  $aisle_uuid
 * @property ?string                                                  $rack_uuid
 * @property ?string                                                  $section_uuid
 * @property ?string                                                  $bin_number
 * @property ?string                                                  $barcode
 * @property ?string                                                  $type
 * @property ?string                                                  $status
 * @property ?string                                                  $capacity
 * @property ?string                                                  $current_volume
 * @property ?array                                                   $dimensions
 * @property ?bool                                                    $is_pickable
 * @property ?bool                                                    $is_replenishable
 * @property ?int                                                     $priority
 * @property ?array                                                   $meta
 * @property ?\Illuminate\Support\Carbon                              $created_at
 * @property ?\Illuminate\Support\Carbon                              $updated_at
 * @property ?\Illuminate\Support\Carbon                              $deleted_at
 * @property WarehouseAisle|null                                      $aisle
 * @property \Illuminate\Database\Eloquent\Collection<int, Inventory> $inventoryItems
 * @property WarehouseRack|null                                       $rack
 * @property WarehouseSection|null                                    $section
 * @property Warehouse|null                                           $warehouse
 * @property WarehouseZone|null                                       $zone
 * @property mixed                                                    $utilization_percentage
 * @property mixed                                                    $available_capacity
 */
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
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'bin_location';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'bin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
     * @var array<string, string>
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
     * @var array<int, string>
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
     * @var array<int, string>
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
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get the zone.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_uuid', 'uuid');
    }

    /**
     * Get the aisle.
     */
    public function aisle(): BelongsTo
    {
        return $this->belongsTo(WarehouseAisle::class, 'aisle_uuid', 'uuid');
    }

    /**
     * Get the rack.
     */
    public function rack(): BelongsTo
    {
        return $this->belongsTo(WarehouseRack::class, 'rack_uuid', 'uuid');
    }

    /**
     * Get the section.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(WarehouseSection::class, 'section_uuid', 'uuid');
    }

    /**
     * Get inventory items in this bin.
     */
    public function inventoryItems(): HasMany
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
