<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\FleetOps\Models\Place;
use Fleetbase\Traits\HasMetaAttributes;

class Warehouse extends Place
{
    use HasMetaAttributes;

    /**
     * Overwrite both place resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'warehouse';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'warehouse';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'                => Json::class,
        'capacity'            => 'decimal:2',
        'current_utilization' => 'decimal:2',
        'is_active'           => 'boolean',
        'is_3pl'              => 'boolean',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = ['utilization_percentage', 'total_zones', 'total_bins'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['sections', 'zones', 'docks'];

    /**
     * Get warehouse sections.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sections()
    {
        return $this->hasMany(WarehouseSection::class, 'warehouse_uuid');
    }

    /**
     * Get warehouse docks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function docks()
    {
        return $this->hasMany(WarehouseDock::class, 'warehouse_uuid');
    }

    /**
     * Get warehouse zones.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function zones()
    {
        return $this->hasMany(WarehouseZone::class, 'warehouse_uuid');
    }

    /**
     * Get warehouse aisles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function aisles()
    {
        return $this->hasMany(WarehouseAisle::class, 'warehouse_uuid');
    }

    /**
     * Get warehouse racks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function racks()
    {
        return $this->hasMany(WarehouseRack::class, 'warehouse_uuid');
    }

    /**
     * Get bin locations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function binLocations()
    {
        return $this->hasMany(BinLocation::class, 'warehouse_uuid');
    }

    /**
     * Get inventory items in this warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'warehouse_uuid');
    }

    /**
     * Get purchase orders for this warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'warehouse_uuid');
    }

    /**
     * Get sales orders for this warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'warehouse_uuid');
    }

    /**
     * Get pick lists for this warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pickLists()
    {
        return $this->hasMany(PickList::class, 'warehouse_uuid');
    }

    /**
     * Get cycle counts for this warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cycleCounts()
    {
        return $this->hasMany(CycleCount::class, 'warehouse_uuid');
    }

    /**
     * Get stock transfers from this warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function outboundTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_uuid');
    }

    /**
     * Get stock transfers to this warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inboundTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_uuid');
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
            ->selectRaw('SUM(pallet_inventories.quantity * pallet_products.unit_cost) as total_value')
            ->value('total_value') ?? 0;
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
     * @param float $volume
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
}
