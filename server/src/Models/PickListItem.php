<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;

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
     * @var array
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
     * @var array
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
     * @var array
     */
    protected $with = ['product', 'variant', 'binLocation'];

    /**
     * Get the pick list.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pickList()
    {
        return $this->belongsTo(PickList::class, 'pick_list_uuid', 'uuid');
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
     * Get the user who picked this item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pickedBy()
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
