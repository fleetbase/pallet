<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Pallet\Traits\HasOperationalAuditTrail;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Illuminate\Support\Facades\DB;

class Wave extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use TracksApiCredential;
    use HasMetaAttributes;
    use HasOperationalAuditTrail;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_waves';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'wave';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'wave';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_uuid',
        'warehouse_uuid',
        'wave_number',
        'type',
        'status',
        'priority',
        'scheduled_at',
        'started_at',
        'completed_at',
        'notes',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'         => Json::class,
        'priority'     => 'integer',
        'scheduled_at' => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = ['total_pick_lists', 'completed_pick_lists'];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['warehouse'];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['wave_number', 'status', 'type', 'notes'];

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
     * Get the pick lists in this wave.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pickLists()
    {
        return $this->hasMany(PickList::class, 'wave_uuid', 'uuid');
    }

    /**
     * Get total pick lists count.
     *
     * @return int
     */
    public function getTotalPickListsAttribute()
    {
        return $this->pickLists()->count();
    }

    /**
     * Get completed pick lists count.
     *
     * @return int
     */
    public function getCompletedPickListsAttribute()
    {
        return $this->pickLists()->where('status', 'completed')->count();
    }

    /**
     * Start the wave.
     *
     * @return bool
     */
    public function start()
    {
        if (!in_array($this->status, ['pending', 'released'], true)) {
            throw new \RuntimeException('Only pending or released waves can be started.');
        }

        $this->status     = 'in_progress';
        $this->started_at = now();

        return $this->save();
    }

    /**
     * Complete the wave.
     *
     * @return bool
     */
    public function complete()
    {
        if ($this->status !== 'in_progress') {
            throw new \RuntimeException('Only in-progress waves can be completed.');
        }

        $this->status       = 'completed';
        $this->completed_at = now();

        return $this->save();
    }

    /**
     * Release the wave: allocate stock for every pending pick list attached
     * to it, generating pick items and hard reservations from the linked
     * sales orders' outstanding lines (FEFO).
     *
     * @return bool
     */
    public function release()
    {
        if ($this->status !== 'pending') {
            throw new \RuntimeException('Only pending waves can be released.');
        }

        $allocated = 0;

        $result = DB::transaction(function () use (&$allocated) {
            $pickLists = $this->pickLists()
                ->where('status', 'pending')
                ->whereNotNull('sales_order_uuid')
                ->doesntHave('items')
                ->lockForUpdate()
                ->get();

            foreach ($pickLists as $pickList) {
                $allocated += $this->allocatePickListItems($pickList);
            }

            $this->status = 'released';

            return $this->save();
        });

        // Log operational audit event
        $this->logAuditEvent(
            AuditEventType::WAVE_RELEASED,
            'Wave Released',
            'released',
            null,
            [
                'wave_number'     => $this->wave_number,
                'warehouse_uuid'  => $this->warehouse_uuid,
                'pick_lists'      => $this->total_pick_lists,
                'items_allocated' => $allocated,
            ]
        );

        return $result;
    }

    /**
     * Explode a pick list's sales-order lines into pick items backed by
     * hard inventory reservations. Allocates what is available; shorted
     * lines stay open on the sales order.
     *
     * @return int number of pick items created
     */
    protected function allocatePickListItems(PickList $pickList): int
    {
        $created    = 1;
        $orderItems = SalesOrderItem::where('sales_order_uuid', $pickList->sales_order_uuid)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($orderItems as $orderItem) {
            $outstanding = max(0, (int) $orderItem->quantity - (int) ($orderItem->quantity_fulfilled ?? 0));

            if ($outstanding <= 0) {
                continue;
            }

            $inventory = Inventory::where('company_uuid', $this->company_uuid)
                ->where('product_uuid', $orderItem->product_uuid)
                ->where('variant_uuid', $orderItem->variant_uuid)
                ->where('warehouse_uuid', $pickList->warehouse_uuid)
                ->whereIn('status', ['active', 'available'])
                ->where('available_quantity', '>', 0)
                ->orderByRaw('CASE WHEN expiry_date_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expiry_date_at')
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                continue;
            }

            $allocate = min($outstanding, (int) $inventory->available_quantity);

            if ($allocate <= 0 || !$inventory->reserve($allocate)) {
                continue;
            }

            InventoryReservation::create([
                'company_uuid'     => $this->company_uuid,
                'product_uuid'     => $orderItem->product_uuid,
                'variant_uuid'     => $orderItem->variant_uuid,
                'inventory_uuid'   => $inventory->uuid,
                'warehouse_uuid'   => $inventory->warehouse_uuid,
                'sales_order_uuid' => $pickList->sales_order_uuid,
                'pick_list_uuid'   => $pickList->uuid,
                'quantity'         => $allocate,
                'status'           => 'active',
                'type'             => 'hard',
                'meta'             => ['source' => 'wave_release', 'wave_uuid' => $this->uuid],
            ]);

            PickListItem::create([
                'company_uuid'       => $this->company_uuid,
                'pick_list_uuid'     => $pickList->uuid,
                'product_uuid'       => $orderItem->product_uuid,
                'variant_uuid'       => $orderItem->variant_uuid,
                'inventory_uuid'     => $inventory->uuid,
                'bin_location_uuid'  => $inventory->bin_location_uuid,
                'quantity_requested' => $allocate,
                'quantity_picked'    => 0,
                'sequence_number'    => $created,
                'status'             => 'pending',
                'lot_number'         => $inventory->lot_number,
                'serial_number'      => $inventory->serial_number,
            ]);

            // point the order line at the reserved row so sales-order
            // fulfillment commits the reservation instead of double-deducting
            if (!$orderItem->inventory_uuid) {
                $orderItem->inventory_uuid = $inventory->uuid;
                $orderItem->save();
            }

            $created++;
        }

        return $created - 1;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->wave_number) {
                $model->wave_number = 'WAVE-' . strtoupper(uniqid());
            }
            if (!$model->status) {
                $model->status = 'pending';
            }
            if (!$model->type) {
                $model->type = 'standard'; // standard, express, bulk
            }
            if (!$model->priority) {
                $model->priority = 5;
            }
        });
    }
}
