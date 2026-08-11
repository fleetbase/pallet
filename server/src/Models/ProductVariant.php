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

class ProductVariant extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use HasMetaAttributes;

    protected $table = 'pallet_product_variants';

    protected $payloadKey = 'product_variant';

    protected $singularName = 'product_variant';

    protected $publicIdType = 'variant';

    protected $fillable = [
        'company_uuid',
        'created_by_uuid',
        'product_uuid',
        'storefront_variant_uuid',
        'name',
        'sku',
        'barcode',
        'option_values',
        'currency',
        'unit_cost',
        'unit_price',
        'sale_price',
        'declared_value',
        'weight',
        'weight_unit',
        'status',
        'meta',
    ];

    protected $searchableColumns = ['name', 'sku', 'barcode', 'storefront_variant_uuid'];

    protected $casts = [
        'meta'           => Json::class,
        'option_values'  => Json::class,
        'unit_cost'      => 'decimal:4',
        'unit_price'     => 'decimal:4',
        'sale_price'     => 'decimal:4',
        'declared_value' => 'decimal:4',
        'weight'         => 'decimal:4',
    ];

    protected $appends = ['incrementing_id', 'display_name', 'total_stock', 'available_stock', 'reserved_stock', 'is_out_of_stock'];

    protected $with = [];

    public function getIncrementingIdAttribute(): ?int
    {
        return static::select('id')->where('uuid', $this->uuid)->value('id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'uuid');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'variant_uuid', 'uuid');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: trim(collect($this->option_values ?? [])->implode(' / ')) ?: ($this->sku ?: ($this->public_id ?? ''));
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->inventories()->sum('quantity');
    }

    public function getAvailableStockAttribute(): int
    {
        return (int) $this->inventories()->sum('available_quantity');
    }

    public function getReservedStockAttribute(): int
    {
        return (int) $this->inventories()->sum('reserved_quantity');
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->available_stock <= 0;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->company_uuid ??= session('company');
            $model->created_by_uuid ??= session('user');
            $model->status ??= 'active';
        });

        static::saved(function ($model) {
            $model->product?->forceFill(['has_variants' => true])->saveQuietly();
        });
    }
}
