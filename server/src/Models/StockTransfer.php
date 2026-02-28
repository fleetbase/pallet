<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\HasMetaAttributes;

class StockTransfer extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use TracksApiCredential;
    use HasMetaAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_stock_transfers';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'transfer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_uuid',
        'from_warehouse_uuid',
        'to_warehouse_uuid',
        'transfer_number',
        'status',
        'type',
        'requested_by_uuid',
        'approved_by_uuid',
        'shipped_at',
        'received_at',
        'notes',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'        => Json::class,
        'shipped_at'  => 'datetime',
        'received_at' => 'datetime',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = ['total_items', 'total_quantity'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['fromWarehouse', 'toWarehouse', 'items'];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['transfer_number', 'status', 'type', 'notes'];

    /**
     * Get the source warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fromWarehouse()
    {
        return $this->belongsTo(\Fleetbase\FleetOps\Models\Place::class, 'from_warehouse_uuid');
    }

    /**
     * Get the destination warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toWarehouse()
    {
        return $this->belongsTo(\Fleetbase\FleetOps\Models\Place::class, 'to_warehouse_uuid');
    }

    /**
     * Get the user who requested the transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function requestedBy()
    {
        return $this->belongsTo(\Fleetbase\Models\User::class, 'requested_by_uuid');
    }

    /**
     * Get the user who approved the transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approvedBy()
    {
        return $this->belongsTo(\Fleetbase\Models\User::class, 'approved_by_uuid');
    }

    /**
     * Get the transfer items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_uuid');
    }

    /**
     * Get total items count.
     *
     * @return int
     */
    public function getTotalItemsAttribute()
    {
        return $this->items()->count();
    }

    /**
     * Get total quantity.
     *
     * @return int
     */
    public function getTotalQuantityAttribute()
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Approve the transfer.
     *
     * @param string|null $userUuid
     * @return bool
     */
    public function approve($userUuid = null)
    {
        $this->status = 'approved';
        if ($userUuid) {
            $this->approved_by_uuid = $userUuid;
        }

        return $this->save();
    }

    /**
     * Ship the transfer.
     *
     * @return bool
     */
    public function ship()
    {
        // Deduct inventory from source warehouse
        foreach ($this->items as $item) {
            $inventory = Inventory::where('product_uuid', $item->product_uuid)
                ->where('warehouse_uuid', $this->from_warehouse_uuid)
                ->first();

            if ($inventory) {
                $inventory->quantity -= $item->quantity;
                $inventory->save();
            }
        }

        $this->status = 'in_transit';
        $this->shipped_at = now();

        return $this->save();
    }

    /**
     * Receive the transfer.
     *
     * @return bool
     */
    public function receive()
    {
        // Add inventory to destination warehouse
        foreach ($this->items as $item) {
            $inventory = Inventory::firstOrCreate(
                [
                    'product_uuid'   => $item->product_uuid,
                    'warehouse_uuid' => $this->to_warehouse_uuid,
                    'company_uuid'   => $this->company_uuid,
                ],
                [
                    'quantity' => 0,
                ]
            );

            $inventory->quantity += $item->quantity_received ?? $item->quantity;
            $inventory->save();
        }

        $this->status = 'completed';
        $this->received_at = now();

        return $this->save();
    }

    /**
     * Cancel the transfer.
     *
     * @return bool
     */
    public function cancel()
    {
        $this->status = 'cancelled';

        return $this->save();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->transfer_number) {
                $model->transfer_number = 'TR-' . strtoupper(uniqid());
            }
            if (!$model->status) {
                $model->status = 'pending';
            }
            if (!$model->type) {
                $model->type = 'standard'; // standard, emergency, replenishment
            }
        });
    }
}
