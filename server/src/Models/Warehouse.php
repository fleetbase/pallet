<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\FleetOps\Models\Place;
use Fleetbase\Models\Company;
use Fleetbase\Models\Model;
use Fleetbase\Models\User;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\SendsWebhooks;
use Fleetbase\Traits\TracksApiCredential;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                                                            $id
 * @property string                                                          $uuid
 * @property ?string                                                         $public_id
 * @property ?string                                                         $company_uuid
 * @property ?string                                                         $created_by_uuid
 * @property ?string                                                         $place_uuid
 * @property ?string                                                         $name
 * @property ?string                                                         $code
 * @property ?string                                                         $type
 * @property ?string                                                         $status
 * @property ?int                                                            $capacity
 * @property ?int                                                            $current_utilization
 * @property ?float                                                          $floor_area_sqm
 * @property ?array                                                          $operating_hours
 * @property ?string                                                         $timezone
 * @property ?string                                                         $phone
 * @property ?string                                                         $email
 * @property ?string                                                         $manager_uuid
 * @property ?int                                                            $total_docks
 * @property ?bool                                                           $is_active
 * @property ?bool                                                           $is_default
 * @property ?array                                                          $meta
 * @property ?\Illuminate\Support\Carbon                                     $created_at
 * @property ?\Illuminate\Support\Carbon                                     $updated_at
 * @property ?\Illuminate\Support\Carbon                                     $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<int, WarehouseAisle>   $aisles
 * @property \Illuminate\Database\Eloquent\Collection<int, BinLocation>      $binLocations
 * @property \Fleetbase\Pallet\Models\Company|null                           $company
 * @property \Fleetbase\Pallet\Models\User|null                              $createdBy
 * @property \Illuminate\Database\Eloquent\Collection<int, CycleCount>       $cycleCounts
 * @property \Illuminate\Database\Eloquent\Collection<int, WarehouseDock>    $docks
 * @property \Illuminate\Database\Eloquent\Collection<int, StockTransfer>    $inboundTransfers
 * @property \Illuminate\Database\Eloquent\Collection<int, Inventory>        $inventories
 * @property \Fleetbase\Pallet\Models\User|null                              $manager
 * @property \Illuminate\Database\Eloquent\Collection<int, StockTransfer>    $outboundTransfers
 * @property \Illuminate\Database\Eloquent\Collection<int, PickList>         $pickLists
 * @property \Fleetbase\Pallet\Models\Place|null                             $place
 * @property \Illuminate\Database\Eloquent\Collection<int, PurchaseOrder>    $purchaseOrders
 * @property \Illuminate\Database\Eloquent\Collection<int, WarehouseRack>    $racks
 * @property \Illuminate\Database\Eloquent\Collection<int, SalesOrder>       $salesOrders
 * @property \Illuminate\Database\Eloquent\Collection<int, WarehouseSection> $sections
 * @property \Illuminate\Database\Eloquent\Collection<int, WarehouseZone>    $zones
 * @property mixed                                                           $utilization_percentage
 * @property mixed                                                           $total_zones
 * @property mixed                                                           $total_bins
 * @property mixed                                                           $address
 */
class Warehouse extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use SendsWebhooks;
    use TracksApiCredential;
    use LogsActivity;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_warehouses';

    /**
     * The payload key for API requests.
     *
     * @var string
     */
    protected $payloadKey = 'warehouse';

    /**
     * The public_id prefix for this model.
     *
     * @var string
     */
    protected $publicIdPrefix = 'warehouse';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'public_id',
        'company_uuid',
        'created_by_uuid',
        'place_uuid',
        'name',
        'code',
        'type',
        'status',
        'capacity',
        'current_utilization',
        'floor_area_sqm',
        'operating_hours',
        'timezone',
        'phone',
        'email',
        'manager_uuid',
        'total_docks',
        // `is_active` is deliberately not fillable — setStatusAttribute derives it
        'is_default',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'operating_hours'     => 'array',
        'meta'                => Json::class,
        'capacity'            => 'integer',
        'current_utilization' => 'integer',
        'floor_area_sqm'      => 'float',
        'is_active'           => 'boolean',
        'is_default'          => 'boolean',
        'total_docks'         => 'integer',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array<int, string>
     */
    protected $appends = ['utilization_percentage', 'total_zones', 'total_bins', 'address'];

    /**
     * Relationships to eager load.
     *
     * @var array<int, string>
     */
    protected $with = ['place', 'sections', 'zones', 'docks'];

    /**
     * `status` and `is_active` both answer "is this warehouse in service", and
     * nothing kept them in step: the create form offered a status select and an
     * Active checkbox side by side, so a new warehouse came back reporting
     * status "active" and is_active false on the very same details panel.
     *
     * `status` is the richer field — it distinguishes inactive from maintenance —
     * so it is the source of truth and is_active follows it.
     */
    public function setStatusAttribute($status): void
    {
        $this->attributes['status']    = $status;
        $this->attributes['is_active'] = $status === 'active';
    }

    /**
     * The company that owns this warehouse.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_uuid', 'uuid');
    }

    /**
     * The user who created this warehouse.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * The geographic/address record for this warehouse.
     * All address, coordinates, and geocoding data lives here.
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'place_uuid', 'uuid');
    }

    /**
     * The user assigned as warehouse manager.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_uuid', 'uuid');
    }

    /**
     * Get warehouse sections.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(WarehouseSection::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get warehouse docks.
     */
    public function docks(): HasMany
    {
        return $this->hasMany(WarehouseDock::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get warehouse zones.
     */
    public function zones(): HasMany
    {
        return $this->hasMany(WarehouseZone::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get warehouse aisles.
     */
    public function aisles(): HasMany
    {
        return $this->hasMany(WarehouseAisle::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get warehouse racks.
     */
    public function racks(): HasMany
    {
        return $this->hasMany(WarehouseRack::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get bin locations.
     */
    public function binLocations(): HasMany
    {
        return $this->hasMany(BinLocation::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get inventory items in this warehouse.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get purchase orders for this warehouse.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get sales orders for this warehouse.
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get pick lists for this warehouse.
     */
    public function pickLists(): HasMany
    {
        return $this->hasMany(PickList::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get cycle counts for this warehouse.
     */
    public function cycleCounts(): HasMany
    {
        return $this->hasMany(CycleCount::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get stock transfers from this warehouse.
     */
    public function outboundTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_uuid', 'uuid');
    }

    /**
     * Get stock transfers to this warehouse.
     */
    public function inboundTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_uuid', 'uuid');
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
     * Get total zones count.
     *
     * @return int
     */
    public function getTotalZonesAttribute()
    {
        return $this->zones()->count();
    }

    /**
     * Get total bins count.
     *
     * @return int
     */
    public function getTotalBinsAttribute()
    {
        return $this->binLocations()->count();
    }

    /**
     * Get total inventory value.
     *
     * @return float
     */
    public function getTotalInventoryValue()
    {
        return $this->inventories()
            ->join('pallet_products', 'pallet_inventories.product_uuid', '=', 'pallet_products.uuid')
            ->leftJoin('pallet_product_variants', 'pallet_inventories.variant_uuid', '=', 'pallet_product_variants.uuid')
            ->selectRaw('SUM(pallet_inventories.quantity * COALESCE(pallet_product_variants.sale_price, pallet_products.sale_price, pallet_inventories.unit_cost, 0)) as total_value')
            ->value('total_value') ?? 0;
    }

    /**
     * Get the formatted address from the linked Place.
     */
    public function getAddressAttribute(): ?string
    {
        return $this->place ? $this->place->address : null;
    }

    /**
     * Get available bin locations.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableBins()
    {
        return $this->binLocations()
            ->where('status', 'active')
            ->whereRaw('current_volume < capacity')
            ->get();
    }

    /**
     * Find optimal bin for product.
     *
     * @param Product $product
     * @param float   $volume
     *
     * @return BinLocation|null
     */
    public function findOptimalBin($product, $volume = 0)
    {
        return $this->binLocations()
            ->where('status', 'active')
            ->where('is_pickable', true)
            ->whereRaw('(capacity - current_volume) >= ?', [$volume])
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Configure Spatie activity log options.
     * Logs only the specified attributes when they change (dirty only).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'code',
                'type',
                'status',
                'is_active',
                'capacity',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
