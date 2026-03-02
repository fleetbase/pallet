<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;

class InventoryReservation extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use TracksApiCredential;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_inventory_reservations';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'reservation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_uuid',
        'product_uuid',
        'inventory_uuid',
        'warehouse_uuid',
        'order_uuid',
        'sales_order_uuid',
        'pick_list_uuid',
        'quantity',
        'reserved_at',
        'expires_at',
        'released_at',
        'status',
        'type',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'        => Json::class,
        'quantity'    => 'integer',
        'reserved_at' => 'datetime',
        'expires_at'  => 'datetime',
        'released_at' => 'datetime',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = ['is_expired', 'is_active'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['product', 'warehouse', 'salesOrder'];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['status', 'type'];

    /**
     * Get the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_uuid');
    }

    /**
     * Get the inventory record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_uuid');
    }

    /**
     * Get the warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid');
    }

    /**
     * Get the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_uuid');
    }

    /**
     * Get the pick list.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pickList()
    {
        return $this->belongsTo(PickList::class, 'pick_list_uuid');
    }

    /**
     * Check if reservation is expired.
     *
     * @return bool
     */
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if reservation is active.
     *
     * @return bool
     */
    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && !$this->is_expired;
    }

    /**
     * Release the reservation.
     *
     * @return bool
     */
    public function release()
    {
        $this->status = 'released';
        $this->released_at = now();

        return $this->save();
    }

    /**
     * Fulfill the reservation.
     *
     * @return bool
     */
    public function fulfill()
    {
        $this->status = 'fulfilled';

        return $this->save();
    }

    /**
     * Scope to get active reservations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->reserved_at) {
                $model->reserved_at = now();
            }
            if (!$model->status) {
                $model->status = 'active';
            }
            if (!$model->type) {
                $model->type = 'soft'; // soft or hard reservation
            }
        });
    }
}
