<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Illuminate\Support\Facades\DB;

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
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'inventory_reservation';

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
        'variant_uuid',
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
    protected $with = ['product', 'variant', 'warehouse', 'salesOrder'];

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
     * Get the warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * Get the sales order.
     *
     * `withOnly([])` discards SalesOrder's own $with, which is
     * ['customer', 'warehouse', 'items.product', 'items.variant', 'items.warehouse',
     * 'items.inventory']. Because this relation sits in this model's $with, every
     * reservation query was hydrating an entire order — its customer, its warehouse,
     * all of its line items and four relations per item — for each row of the
     * reservations list, and the resource then emitted none of it.
     *
     * A reservation only ever needs the order's identity, so nothing below the order
     * is loaded. Anything that needs the line items should load them explicitly.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_uuid', 'uuid')->withOnly([]);
    }

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
        if ($this->status !== 'active') {
            return false;
        }

        $result = DB::transaction(function () {
            // re-check under lock: a concurrent release/fulfill may already have
            // transitioned this reservation, and releasing twice frees stock twice
            $locked = static::whereKey($this->getKey())->lockForUpdate()->first();

            if (!$locked || $locked->status !== 'active') {
                return false;
            }

            if ($locked->inventory_uuid) {
                $inventory = $locked->inventory()->lockForUpdate()->first();

                if (!$inventory || !$inventory->releaseReservation($locked->quantity)) {
                    return false;
                }
            }

            $locked->status      = 'released';
            $locked->released_at = now();

            return $locked->save();
        });

        if ($result) {
            $this->refresh();
        }

        return $result;
    }

    /**
     * Fulfill the reservation.
     *
     * @return bool
     */
    public function fulfill()
    {
        if ($this->status !== 'active') {
            return false;
        }

        $result = DB::transaction(function () {
            // re-check under lock: a concurrent release/fulfill may already have
            // transitioned this reservation, and committing twice deducts twice
            $locked = static::whereKey($this->getKey())->lockForUpdate()->first();

            if (!$locked || $locked->status !== 'active') {
                return false;
            }

            if ($locked->inventory_uuid) {
                $inventory = $locked->inventory()->lockForUpdate()->first();

                if (!$inventory || !$inventory->commitReserved($locked->quantity)) {
                    return false;
                }
            }

            $locked->status = 'fulfilled';

            return $locked->save();
        });

        if ($result) {
            $this->refresh();
        }

        return $result;
    }

    /**
     * Scope to get active reservations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
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
