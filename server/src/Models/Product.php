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

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                                                                  $id
 * @property string                                                                $uuid
 * @property ?string                                                               $public_id
 * @property ?string                                                               $company_uuid
 * @property ?string                                                               $created_by_uuid
 * @property ?string                                                               $category_uuid
 * @property ?string                                                               $supplier_uuid
 * @property ?string                                                               $storefront_product_uuid
 * @property ?string                                                               $photo_uuid
 * @property ?string                                                               $internal_id
 * @property ?string                                                               $name
 * @property ?string                                                               $description
 * @property ?string                                                               $sku
 * @property ?string                                                               $barcode
 * @property ?string                                                               $currency
 * @property ?string                                                               $unit_cost
 * @property ?string                                                               $unit_price
 * @property ?string                                                               $sale_price
 * @property ?string                                                               $declared_value
 * @property ?string                                                               $weight
 * @property ?string                                                               $weight_unit
 * @property ?string                                                               $length
 * @property ?string                                                               $width
 * @property ?string                                                               $height
 * @property ?string                                                               $dimensions_unit
 * @property ?array                                                                $dimensions
 * @property ?bool                                                                 $has_variants
 * @property ?bool                                                                 $is_serialized
 * @property ?bool                                                                 $is_lot_tracked
 * @property ?bool                                                                 $is_kit
 * @property ?bool                                                                 $is_perishable
 * @property ?bool                                                                 $requires_quality_check
 * @property ?int                                                                  $reorder_point
 * @property ?int                                                                  $reorder_quantity
 * @property ?int                                                                  $shelf_life_days
 * @property ?string                                                               $status
 * @property ?string                                                               $slug
 * @property ?array                                                                $meta
 * @property ?\Illuminate\Support\Carbon                                           $created_at
 * @property ?\Illuminate\Support\Carbon                                           $updated_at
 * @property ?\Illuminate\Support\Carbon                                           $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<int, Batch>                  $batches
 * @property \Fleetbase\Models\Category|null                                       $category
 * @property \Illuminate\Database\Eloquent\Collection<int, \Fleetbase\Models\File> $files
 * @property \Illuminate\Database\Eloquent\Collection<int, Inventory>              $inventories
 * @property \Illuminate\Database\Eloquent\Collection<int, ProductKitComponent>    $kitComponents
 * @property Supplier|null                                                         $supplier
 * @property \Illuminate\Database\Eloquent\Collection<int, ProductVariant>         $variants
 * @property mixed                                                                 $incrementing_id
 * @property mixed                                                                 $total_stock
 * @property mixed                                                                 $available_stock
 * @property mixed                                                                 $reserved_stock
 * @property mixed                                                                 $is_out_of_stock
 * @property mixed                                                                 $variant_count
 */
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

    /**
     * @var array<int, string>
     */
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

    /**
     * @var array<string, string>
     */
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

    /**
     * @var array<int, string>
     */
    protected $appends = ['incrementing_id', 'total_stock', 'available_stock', 'reserved_stock', 'is_out_of_stock', 'variant_count'];

    /**
     * @var array<int, string>
     */
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

        static::deleting(function ($model) {
            $model->guardAgainstOrphanedStock();
        });
    }

    /**
     * Products soft-delete, so deleting one left its inventory rows behind, live
     * and still listed — the relation simply resolved to nothing and the inventory
     * screen rendered the row as "Untitled product". Phantom stock in a warehouse
     * system is worse than a refused delete, so:
     *
     *   - refuse outright while any of that inventory still holds stock, and
     *   - otherwise take the empty inventory rows down with the product.
     *
     * @throws \Exception when the product still holds stock
     */
    public function guardAgainstOrphanedStock(): void
    {
        // batches carry their own quantities, so a product can read as empty on the
        // inventory table while a batch still says it holds a hundred units
        $onHand = (int) $this->inventories()->sum('quantity') + (int) $this->batches()->sum('quantity');

        if ($onHand > 0) {
            throw new \Exception('Cannot delete "' . $this->name . '" while it still holds ' . $onHand . ' units of stock. Adjust the stock to zero first.');
        }

        // Only the product's own records go. Order items, stock transactions,
        // adjustments, cycle count items and pick list items also carry a
        // product_uuid, but those are history and deleting a product must not
        // rewrite it.
        $this->inventories()->delete();
        $this->batches()->delete();
        $this->variants()->delete();
    }

    /**
     * bulkRemove() mass-deletes through the query builder, which does not fire
     * model events — so the deleting hook above never ran for the console's own
     * bulk delete, the one path that actually produced the orphans. Walk the
     * records so both paths behave the same.
     */
    public function bulkRemove($ids = [])
    {
        return DB::transaction(function () use ($ids) {
            $count = 0;

            foreach ($this->resolveRecordsForBulkRemoval($ids) as $product) {
                $product->delete();
                $count++;
            }

            return $count;
        });
    }

    /**
     * The same selection bulkRemove() makes in core, including its company scope.
     */
    protected function resolveRecordsForBulkRemoval($ids = [])
    {
        $query = $this->where(function ($q) use ($ids) {
            $publicIdColumn = $this->getQualifiedPublicId();

            $q->whereIn($this->getQualifiedKeyName(), $ids);
            if ($this->isColumn($publicIdColumn)) {
                $q->orWhereIn($publicIdColumn, $ids);
            }
        });

        $companyUuid = session('company');
        if ($companyUuid && $this->isColumn('company_uuid')) {
            $query->where($this->qualifyColumn('company_uuid'), $companyUuid);
        }

        return $query->get();
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
