<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                        $id
 * @property string                      $uuid
 * @property ?string                     $public_id
 * @property ?string                     $company_uuid
 * @property ?string                     $pick_list_uuid
 * @property ?string                     $product_uuid
 * @property ?string                     $variant_uuid
 * @property ?string                     $inventory_uuid
 * @property ?string                     $bin_location_uuid
 * @property ?string                     $sales_order_item_uuid
 * @property ?int                        $quantity_requested
 * @property ?int                        $quantity_picked
 * @property ?int                        $sequence_number
 * @property ?string                     $status
 * @property ?\Illuminate\Support\Carbon $picked_at
 * @property ?string                     $picked_by_uuid
 * @property ?string                     $lot_number
 * @property ?string                     $serial_number
 * @property ?string                     $notes
 * @property ?array                      $meta
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property BinLocation|null            $binLocation
 * @property Inventory|null              $inventory
 * @property PickList|null               $pickList
 * @property \Fleetbase\Models\User|null $pickedBy
 * @property Product|null                $product
 * @property ProductVariant|null         $variant
 */
class PickListItem extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_pick_list_items';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'pick_list_item';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'pick_item';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_uuid',
        'pick_list_uuid',
        'product_uuid',
        'variant_uuid',
        'inventory_uuid',
        'bin_location_uuid',
        'sales_order_item_uuid',
        'quantity_requested',
        'quantity_picked',
        'sequence_number',
        'status',
        'picked_at',
        'picked_by_uuid',
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
        'meta'               => Json::class,
        'quantity_requested' => 'integer',
        'quantity_picked'    => 'integer',
        'sequence_number'    => 'integer',
        'picked_at'          => 'datetime',
    ];

    /**
     * Relationships to eager load.
     *
     * @var array<int, string>
     */
    protected $with = ['product', 'variant', 'binLocation'];

    /**
     * Get the pick list.
     */
    public function pickList(): BelongsTo
    {
        return $this->belongsTo(PickList::class, 'pick_list_uuid', 'uuid');
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
     * Get the user who picked this item.
     */
    public function pickedBy(): BelongsTo
    {
        return $this->belongsTo(\Fleetbase\Models\User::class, 'picked_by_uuid', 'uuid');
    }

    /**
     * Mark as picked.
     *
     * @param int         $quantity
     * @param string|null $userUuid
     *
     * @return bool
     */
    public function markPicked($quantity, $userUuid = null)
    {
        $pickList = $this->pickList()->first();

        if (!$pickList || $pickList->status !== 'in_progress') {
            throw new \RuntimeException('Items can only be picked while the pick list is in progress.');
        }

        $quantity = (int) $quantity;

        if ($quantity <= 0 || $quantity > (int) $this->quantity_requested) {
            throw new \RuntimeException('Picked quantity must be between one and the requested quantity.');
        }

        $this->quantity_picked = $quantity;
        $this->status          = 'picked';
        $this->picked_at       = now();
        if ($userUuid) {
            $this->picked_by_uuid = $userUuid;
        }

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
                $model->status = 'pending';
            }
            if (!$model->quantity_picked) {
                $model->quantity_picked = 0;
            }
        });
    }
}
