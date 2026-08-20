<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Support\Facades\DB;

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
     * @var array
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
     * @var array
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
     * @var array
     */
    protected $appends = ['has_discrepancy'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['product', 'variant', 'binLocation'];

    /**
     * Get the cycle count.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cycleCount()
    {
        return $this->belongsTo(CycleCount::class, 'cycle_count_uuid', 'uuid');
    }

    /**
     * Get the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'uuid');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_uuid', 'uuid');
    }

    /**
     * Get the inventory record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_uuid', 'uuid');
    }

    /**
     * Get the bin location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function binLocation()
    {
        return $this->belongsTo(BinLocation::class, 'bin_location_uuid', 'uuid');
    }

    /**
     * Get the user who counted this item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function countedBy()
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
