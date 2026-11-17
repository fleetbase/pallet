<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                        $id
 * @property string                      $uuid
 * @property ?string                     $public_id
 * @property ?string                     $company_uuid
 * @property ?string                     $cycle_count_uuid
 * @property ?string                     $product_uuid
 * @property ?string                     $variant_uuid
 * @property ?string                     $inventory_uuid
 * @property ?string                     $bin_location_uuid
 * @property ?int                        $expected_quantity
 * @property ?int                        $counted_quantity
 * @property ?int                        $variance
 * @property ?string                     $status
 * @property ?\Illuminate\Support\Carbon $counted_at
 * @property ?string                     $counted_by_uuid
 * @property ?string                     $lot_number
 * @property ?string                     $serial_number
 * @property ?string                     $notes
 * @property ?array                      $meta
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property BinLocation|null            $binLocation
 * @property \Fleetbase\Models\User|null $countedBy
 * @property CycleCount|null             $cycleCount
 * @property Inventory|null              $inventory
 * @property Product|null                $product
 * @property ProductVariant|null         $variant
 * @property mixed                       $has_discrepancy
 */
class CycleCountItem extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_cycle_count_items';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'cycle_count_item';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'count_item';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_uuid',
        'cycle_count_uuid',
        'product_uuid',
        'variant_uuid',
        'inventory_uuid',
        'bin_location_uuid',
        'expected_quantity',
        'counted_quantity',
        'variance',
        'status',
        'counted_at',
        'counted_by_uuid',
        'lot_number',
        'serial_number',
        'notes',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta'              => Json::class,
        'expected_quantity' => 'integer',
        'counted_quantity'  => 'integer',
        'variance'          => 'integer',
        'counted_at'        => 'datetime',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array<int, string>
     */
    protected $appends = ['has_discrepancy'];

    /**
     * Relationships to eager load.
     *
     * @var array<int, string>
     */
    protected $with = ['product', 'variant', 'binLocation'];

    /**
     * Get the cycle count.
     */
    public function cycleCount(): BelongsTo
    {
        return $this->belongsTo(CycleCount::class, 'cycle_count_uuid', 'uuid');
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_uuid', 'uuid');
    }

    /**
     * Get the inventory record.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_uuid', 'uuid');
    }

    /**
     * Get the bin location.
     */
    public function binLocation(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class, 'bin_location_uuid', 'uuid');
    }

    /**
     * Get the user who counted this item.
     */
    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(\Fleetbase\Models\User::class, 'counted_by_uuid', 'uuid');
    }

    /**
     * Check if there's a discrepancy.
     *
     * @return bool
     */
    public function getHasDiscrepancyAttribute()
    {
        return $this->expected_quantity != $this->counted_quantity;
    }

    /**
     * Record count.
     *
     * @param int         $quantity
     * @param string|null $userUuid
     *
     * @return bool
     */
    public function recordCount($quantity, $userUuid = null)
    {
        $cycleCount = $this->cycleCount()->first();

        if (!$cycleCount || $cycleCount->status !== 'in_progress') {
            throw new \RuntimeException('Counts can only be recorded while the cycle count is in progress.');
        }

        $this->counted_quantity = $quantity;
        $this->variance         = $quantity - $this->expected_quantity;
        $this->status           = 'counted';
        $this->counted_at       = now();
        if ($userUuid) {
            $this->counted_by_uuid = $userUuid;
        }

        return $this->save();
    }

    /**
     * Apply inventory adjustment for discrepancy.
     *
     * @return StockAdjustment|null
     */
    public function applyAdjustment()
    {
        if (!$this->has_discrepancy) {
            return null;
        }

        return DB::transaction(function () {
            $inventory = Inventory::where('company_uuid', $this->company_uuid)
                ->where('uuid', $this->inventory_uuid)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new \RuntimeException('No inventory record was found for this cycle count item.');
            }

            $beforeQuantity = (int) $inventory->quantity;
            $afterQuantity  = (int) $this->counted_quantity;

            if ($afterQuantity < (int) $inventory->reserved_quantity) {
                throw new \RuntimeException('Cycle count approval cannot reduce on-hand inventory below reserved stock.');
            }

            $inventory->quantity        = $afterQuantity;
            $inventory->last_counted_at = now();
            $inventory->syncAvailableQuantity();
            $inventory->save();
            $inventory->recordStockTransaction('adjusted', $afterQuantity - $beforeQuantity, [
                'source'           => 'cycle_count',
                'cycle_count_uuid' => $this->cycle_count_uuid,
                'count_item_uuid'  => $this->uuid,
            ]);

            return StockAdjustment::create([
                'company_uuid'      => $this->company_uuid,
                'created_by_uuid'   => $this->counted_by_uuid,
                'product_uuid'      => $this->product_uuid,
                'variant_uuid'      => $this->variant_uuid,
                'inventory_uuid'    => $this->inventory_uuid,
                'warehouse_uuid'    => $inventory->warehouse_uuid,
                'quantity'          => $afterQuantity - $beforeQuantity,
                'before_quantity'   => $beforeQuantity,
                'after_quantity'    => $afterQuantity,
                'type'              => 'correction',
                'reason'            => "Cycle count adjustment: {$this->cycleCount->count_number}",
                'approval_required' => false,
            ]);
        });
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->status) {
                $model->status = 'pending';
            }
            if (!$model->counted_quantity) {
                $model->counted_quantity = 0;
            }
        });

        static::saving(function ($model) {
            if ($model->isDirty(['expected_quantity', 'counted_quantity'])) {
                $model->variance = $model->counted_quantity - $model->expected_quantity;
            }
        });
    }
}
