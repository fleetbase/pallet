<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Company;
use Fleetbase\Models\Model;
use Fleetbase\Models\Place;
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
     * @var array
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
        'is_active',
        'is_default',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
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
     * @var array
     */
    protected $appends = ['utilization_percentage', 'total_zones', 'total_bins', 'address'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['place', 'sections', 'zones', 'docks'];

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
     *
     * @return HasMany
     */
    public function sections()
    {
        return $this->hasMany(WarehouseSection::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get warehouse docks.
     *
     * @return HasMany
     */
    public function docks()
    {
        return $this->hasMany(WarehouseDock::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get warehouse zones.
     *
     * @return HasMany
     */
    public function zones()
    {
        return $this->hasMany(WarehouseZone::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get warehouse aisles.
     *
     * @return HasMany
     */
    public function aisles()
    {
        return $this->hasMany(WarehouseAisle::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get warehouse racks.
     *
     * @return HasMany
     */
    public function racks()
    {
        return $this->hasMany(WarehouseRack::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get bin locations.
     *
     * @return HasMany
     */
    public function binLocations()
    {
        return $this->hasMany(BinLocation::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get inventory items in this warehouse.
     *
     * @return HasMany
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get purchase orders for this warehouse.
     *
     * @return HasMany
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get sales orders for this warehouse.
     *
     * @return HasMany
     */
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get pick lists for this warehouse.
     *
     * @return HasMany
     */
    public function pickLists()
    {
        return $this->hasMany(PickList::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get cycle counts for this warehouse.
     *
     * @return HasMany
     */
    public function cycleCounts()
    {
        return $this->hasMany(CycleCount::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get stock transfers from this warehouse.
     *
     * @return HasMany
     */
    public function outboundTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_uuid', 'uuid');
    }

    /**
     * Get stock transfers to this warehouse.
     *
     * @return HasMany
     */
    public function inboundTransfers()
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
