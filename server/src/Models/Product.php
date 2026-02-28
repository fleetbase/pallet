<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\FleetOps\Models\Entity;
use Fleetbase\Traits\HasMetaAttributes;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Entity
{
    use HasMetaAttributes;

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'product';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    public $publicIdType = 'product';

    /**
     * Filterable parameters.
     *
     * @var array
     */
    protected $filterParams = [
        'facilitator',
        'facilitator_type',
        'category',
        'supplier',
        'status',
        'is_serialized',
        'is_lot_tracked',
        'is_kit',
    ];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['name', 'description', 'sku', 'barcode', 'internal_id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'                => Json::class,
        'dimensions'          => Json::class,
        'variants'            => Json::class,
        'kit_components'      => Json::class,
        'is_serialized'       => 'boolean',
        'is_lot_tracked'      => 'boolean',
        'is_kit'              => 'boolean',
        'is_perishable'       => 'boolean',
        'requires_quality_check' => 'boolean',
        'weight'              => 'decimal:2',
        'unit_cost'           => 'decimal:2',
        'unit_price'          => 'decimal:2',
        'reorder_point'       => 'integer',
        'reorder_quantity'    => 'integer',
        'shelf_life_days'     => 'integer',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = ['total_stock', 'available_stock'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['category', 'supplier'];

    /**
     * Get the product category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(\Fleetbase\FleetOps\Models\Category::class, 'category_uuid');
    }

    /**
     * Get the primary supplier.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_uuid');
    }

    /**
     * Get all inventory records for this product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'product_uuid');
    }

    /**
     * Get all batches for this product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function batches()
    {
        return $this->hasMany(Batch::class, 'product_uuid');
    }

    /**
     * Get kit components if this is a kit product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kitComponents()
    {
        return $this->hasMany(ProductKitComponent::class, 'kit_product_uuid');
    }

    /**
     * Get total stock across all warehouses.
     *
     * @return int
     */
    public function getTotalStockAttribute()
    {
        return $this->inventories()->sum('quantity');
    }

    /**
     * Get available stock (total - reserved).
     *
     * @return int
     */
    public function getAvailableStockAttribute()
    {
        $total = $this->total_stock;
        $reserved = InventoryReservation::where('product_uuid', $this->uuid)
            ->where('status', 'active')
            ->sum('quantity');

        return max(0, $total - $reserved);
    }

    /**
     * Check if product needs reordering.
     *
     * @return bool
     */
    public function needsReorder()
    {
        return $this->available_stock <= $this->reorder_point;
    }

    /**
     * Get stock level by warehouse.
     *
     * @param string $warehouseUuid
     * @return int
     */
    public function getStockByWarehouse($warehouseUuid)
    {
        return $this->inventories()
            ->where('warehouse_uuid', $warehouseUuid)
            ->sum('quantity');
    }

    /**
     * Reserve inventory for an order.
     *
     * @param int $quantity
     * @param string $orderUuid
     * @param string|null $warehouseUuid
     * @return InventoryReservation|null
     */
    public function reserveInventory($quantity, $orderUuid, $warehouseUuid = null)
    {
        if ($this->available_stock < $quantity) {
            return null;
        }

        return InventoryReservation::create([
            'company_uuid'   => session('company'),
            'product_uuid'   => $this->uuid,
            'order_uuid'     => $orderUuid,
            'warehouse_uuid' => $warehouseUuid,
            'quantity'       => $quantity,
            'status'         => 'active',
        ]);
    }
    /**
     * Configure Spatie activity log options.
     * Logs only the specified attributes when they change (dirty only).
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'sku',
                'description',
                'price',
                'cost',
                'weight',
                'category',
                'status',
                'barcode',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

}