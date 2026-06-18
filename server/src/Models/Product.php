<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasInternalId;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasInternalId;
    use HasApiModelBehavior;
    use HasMetaAttributes;
    use LogsActivity;

    protected $table = 'pallet_products';

    protected $payloadKey = 'product';

    public $publicIdType = 'product';

    protected $fillable = [
        'company_uuid',
        'created_by_uuid',
        'category_uuid',
        'supplier_uuid',
        'storefront_product_uuid',
        'photo_uuid',
        'internal_id',
        'name',
        'description',
        'sku',
        'barcode',
        'currency',
        'unit_cost',
        'unit_price',
        'sale_price',
        'declared_value',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimensions_unit',
        'dimensions',
        'has_variants',
        'is_serialized',
        'is_lot_tracked',
        'is_kit',
        'is_perishable',
        'requires_quality_check',
        'reorder_point',
        'reorder_quantity',
        'shelf_life_days',
        'status',
        'slug',
        'meta',
    ];

    protected $filterParams = [
        'category',
        'supplier',
        'storefront_product_uuid',
        'status',
        'has_variants',
        'is_serialized',
        'is_lot_tracked',
        'is_kit',
    ];

    protected $searchableColumns = ['name', 'description', 'sku', 'barcode', 'internal_id'];

    protected $casts = [
        'meta'                   => Json::class,
        'dimensions'             => Json::class,
        'has_variants'           => 'boolean',
        'is_serialized'          => 'boolean',
        'is_lot_tracked'         => 'boolean',
        'is_kit'                 => 'boolean',
        'is_perishable'          => 'boolean',
        'requires_quality_check' => 'boolean',
        'weight'                 => 'decimal:4',
        'length'                 => 'decimal:4',
        'width'                  => 'decimal:4',
        'height'                 => 'decimal:4',
        'unit_cost'              => 'decimal:4',
        'unit_price'             => 'decimal:4',
        'sale_price'             => 'decimal:4',
        'declared_value'         => 'decimal:4',
        'reorder_point'          => 'integer',
        'reorder_quantity'       => 'integer',
        'shelf_life_days'        => 'integer',
    ];

    protected $appends = ['incrementing_id', 'total_stock', 'available_stock', 'reserved_stock', 'is_out_of_stock', 'variant_count'];

    protected $with = ['category', 'supplier', 'variants', 'files'];

    public function getIncrementingIdAttribute(): ?int
    {
        return static::select('id')->where('uuid', $this->uuid)->value('id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(\Fleetbase\Models\Category::class, 'category_uuid', 'uuid');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_uuid', 'uuid');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_uuid', 'uuid');
    }

    public function files(): HasMany
    {
        return $this->hasMany(\Fleetbase\Models\File::class, 'subject_uuid', 'uuid')->where('subject_type', 'pallet:product');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'product_uuid', 'uuid');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'product_uuid', 'uuid');
    }

    public function kitComponents(): HasMany
    {
        return $this->hasMany(ProductKitComponent::class, 'kit_product_uuid', 'uuid');
    }

    public function getVariantCountAttribute(): int
    {
        return $this->variants()->count();
    }

    public function getTotalStockAttribute(): int
    {
        $query = $this->inventories();

        if (!$this->has_variants) {
            $query->whereNull('variant_uuid');
        }

        return (int) $query->sum('quantity');
    }

    public function getAvailableStockAttribute(): int
    {
        $query = $this->inventories();

        if (!$this->has_variants) {
            $query->whereNull('variant_uuid');
        }

        return (int) $query->sum('available_quantity');
    }

    public function getReservedStockAttribute(): int
    {
        $query = $this->inventories();

        if (!$this->has_variants) {
            $query->whereNull('variant_uuid');
        }

        return (int) $query->sum('reserved_quantity');
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->available_stock <= 0;
    }

    public function needsReorder(): bool
    {
        return $this->reorder_point !== null && $this->available_stock <= $this->reorder_point;
    }

    public function getStockByWarehouse($warehouseUuid, ?string $variantUuid = null): int
    {
        return (int) $this->inventories()
            ->where('warehouse_uuid', $warehouseUuid)
            ->when($variantUuid, fn ($query) => $query->where('variant_uuid', $variantUuid))
            ->when(!$variantUuid && !$this->has_variants, fn ($query) => $query->whereNull('variant_uuid'))
            ->sum('quantity');
    }

    public function reserveInventory($quantity, $orderUuid, $warehouseUuid = null, ?string $variantUuid = null): ?InventoryReservation
    {
        $quantity = (int) $quantity;

        if ($quantity <= 0) {
            return null;
        }

        return DB::transaction(function () use ($quantity, $orderUuid, $warehouseUuid, $variantUuid) {
            $inventory = $this->inventories()
                ->where('company_uuid', $this->company_uuid ?? session('company'))
                ->when($variantUuid, fn ($query) => $query->where('variant_uuid', $variantUuid))
                ->when(!$variantUuid && !$this->has_variants, fn ($query) => $query->whereNull('variant_uuid'))
                ->when($warehouseUuid, fn ($query) => $query->where('warehouse_uuid', $warehouseUuid))
                ->whereIn('status', ['active', 'available'])
                ->where('available_quantity', '>=', $quantity)
                ->orderByRaw('CASE WHEN expiry_date_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expiry_date_at')
                ->lockForUpdate()
                ->first();

            if (!$inventory || !$inventory->reserve($quantity)) {
                return null;
            }

            return InventoryReservation::create([
                'company_uuid'    => $this->company_uuid ?? session('company'),
                'product_uuid'    => $this->uuid,
                'variant_uuid'    => $variantUuid,
                'inventory_uuid'  => $inventory->uuid,
                'order_uuid'      => $orderUuid,
                'warehouse_uuid'  => $inventory->warehouse_uuid,
                'quantity'        => $quantity,
                'status'          => 'active',
            ]);
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->company_uuid ??= session('company');
            $model->created_by_uuid ??= session('user');
            $model->status ??= 'active';
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'sku',
                'description',
                'unit_price',
                'unit_cost',
                'status',
                'barcode',
                'has_variants',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
