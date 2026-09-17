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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                                                             $id
 * @property string                                                           $uuid
 * @property ?string                                                          $public_id
 * @property ?string                                                          $company_uuid
 * @property ?string                                                          $from_warehouse_uuid
 * @property ?string                                                          $to_warehouse_uuid
 * @property ?string                                                          $transfer_number
 * @property ?string                                                          $status
 * @property ?string                                                          $type
 * @property ?string                                                          $requested_by_uuid
 * @property ?string                                                          $approved_by_uuid
 * @property ?\Illuminate\Support\Carbon                                      $shipped_at
 * @property ?\Illuminate\Support\Carbon                                      $received_at
 * @property ?string                                                          $notes
 * @property ?array                                                           $meta
 * @property ?\Illuminate\Support\Carbon                                      $created_at
 * @property ?\Illuminate\Support\Carbon                                      $updated_at
 * @property ?\Illuminate\Support\Carbon                                      $deleted_at
 * @property \Fleetbase\Models\User|null                                      $approvedBy
 * @property Warehouse|null                                                   $fromWarehouse
 * @property \Illuminate\Database\Eloquent\Collection<int, StockTransferItem> $items
 * @property \Fleetbase\Models\User|null                                      $requestedBy
 * @property Warehouse|null                                                   $toWarehouse
 * @property mixed                                                            $total_items
 * @property mixed                                                            $total_quantity
 */
class StockTransfer extends Model
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
    protected $table = 'pallet_stock_transfers';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'stock_transfer';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'transfer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
     * @var array<string, string>
     */
    protected $casts = [
        'meta'        => Json::class,
        'shipped_at'  => 'datetime',
        'received_at' => 'datetime',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array<int, string>
     */
    protected $appends = ['total_items', 'total_quantity'];

    /**
     * Relationships to eager load.
     *
     * @var array<int, string>
     */
    protected $with = ['fromWarehouse', 'toWarehouse', 'items.product', 'items.variant'];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchableColumns = ['transfer_number', 'status', 'type', 'notes'];

    /**
     * Get the source warehouse.
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_uuid', 'uuid');
    }

    /**
     * Get the destination warehouse.
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_uuid', 'uuid');
    }

    /**
     * Get the user who requested the transfer.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(\Fleetbase\Models\User::class, 'requested_by_uuid', 'uuid');
    }

    /**
     * Get the user who approved the transfer.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\Fleetbase\Models\User::class, 'approved_by_uuid', 'uuid');
    }

    /**
     * Get the transfer items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_uuid', 'uuid');
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
     *
     * @return bool
     */
    public function approve($userUuid = null)
    {
        if ($this->status !== 'pending') {
            throw new \RuntimeException('Only pending stock transfers can be approved.');
        }

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
        if ($this->status !== 'approved') {
            throw new \RuntimeException('Only approved stock transfers can be shipped.');
        }

        $result = DB::transaction(function () {
            foreach ($this->items as $item) {
                $inventory = Inventory::where('company_uuid', $this->company_uuid)
                    ->where('product_uuid', $item->product_uuid)
                    ->where('variant_uuid', $item->variant_uuid)
                    ->where('warehouse_uuid', $this->from_warehouse_uuid)
                    ->whereIn('status', ['active', 'available'])
                    ->lockForUpdate()
                    ->first();

                if (!$inventory || $inventory->available_quantity < $item->quantity) {
                    throw new \RuntimeException('Insufficient source inventory for stock transfer item.');
                }

                $inventory->deduct($item->quantity, 'transferred');
            }

            $this->status     = 'in_transit';
            $this->shipped_at = now();

            return $this->save();
        });

        // Log operational audit event
        $this->logAuditEvent(
            AuditEventType::STOCK_TRANSFER,
            'Stock Transfer Shipped',
            'shipped',
            null,
            [
                'transfer_number'     => $this->transfer_number,
                'from_warehouse_uuid' => $this->from_warehouse_uuid,
                'to_warehouse_uuid'   => $this->to_warehouse_uuid,
                'total_items'         => $this->total_items,
                'total_quantity'      => $this->total_quantity,
            ]
        );

        return $result;
    }

    /**
     * Receive the transfer.
     *
     * @return bool
     */
    public function receive()
    {
        if ($this->status !== 'in_transit') {
            throw new \RuntimeException('Only in-transit stock transfers can be received.');
        }

        $result = DB::transaction(function () {
            foreach ($this->items as $item) {
                if ((int) $item->quantity - (int) $item->quantity_received <= 0) {
                    continue;
                }

                $inventory = Inventory::firstOrCreate(
                    [
                        'product_uuid'   => $item->product_uuid,
                        'variant_uuid'   => $item->variant_uuid,
                        'warehouse_uuid' => $this->to_warehouse_uuid,
                        'company_uuid'   => $this->company_uuid,
                    ],
                    [
                        'quantity'           => 0,
                        'available_quantity' => 0,
                        'reserved_quantity'  => 0,
                        'status'             => 'active',
                    ]
                );

                // `?? $item->quantity` never fired: both controllers that create a
                // transfer item write quantity_received as 0, not null, and 0 ?? x is
                // 0. So a completed transfer deducted the full quantity at the source
                // and added nothing at the destination — the stock simply vanished.
                $receiving = (int) $item->quantity - (int) $item->quantity_received;

                if ($receiving <= 0) {
                    continue;
                }

                $inventory->add($receiving, 'transferred');
                $item->recordReceived((int) $item->quantity_received + $receiving);
            }

            $this->status      = 'completed';
            $this->received_at = now();

            return $this->save();
        });

        // Log operational audit event
        $this->logAuditEvent(
            AuditEventType::STOCK_TRANSFER,
            'Stock Transfer Completed',
            'completed',
            null,
            [
                'transfer_number'     => $this->transfer_number,
                'from_warehouse_uuid' => $this->from_warehouse_uuid,
                'to_warehouse_uuid'   => $this->to_warehouse_uuid,
                'total_items'         => $this->total_items,
                'total_quantity'      => $this->total_quantity,
            ]
        );

        return $result;
    }

    /**
     * Cancel the transfer.
     *
     * @return bool
     */
    public function cancel()
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            throw new \RuntimeException('Completed or cancelled stock transfers cannot be cancelled.');
        }

        // stock deducted by ship() must return to the source warehouse
        $restoreStock = $this->status === 'in_transit';

        $result = DB::transaction(function () use ($restoreStock) {
            if ($restoreStock) {
                foreach ($this->items as $item) {
                    $inventory = Inventory::where('company_uuid', $this->company_uuid)
                        ->where('product_uuid', $item->product_uuid)
                        ->where('variant_uuid', $item->variant_uuid)
                        ->where('warehouse_uuid', $this->from_warehouse_uuid)
                        ->whereIn('status', ['active', 'available'])
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory) {
                        $inventory = Inventory::create([
                            'company_uuid'       => $this->company_uuid,
                            'product_uuid'       => $item->product_uuid,
                            'variant_uuid'       => $item->variant_uuid,
                            'warehouse_uuid'     => $this->from_warehouse_uuid,
                            'quantity'           => 0,
                            'reserved_quantity'  => 0,
                            'available_quantity' => 0,
                            'status'             => 'active',
                        ]);
                    }

                    $inventory->add($item->quantity, 'transfer_cancelled');
                }
            }

            $this->status = 'cancelled';

            return $this->save();
        });

        // Log operational audit event
        $this->logAuditEvent(
            AuditEventType::STOCK_TRANSFER,
            'Stock Transfer Cancelled',
            'cancelled',
            null,
            [
                'transfer_number'     => $this->transfer_number,
                'from_warehouse_uuid' => $this->from_warehouse_uuid,
                'to_warehouse_uuid'   => $this->to_warehouse_uuid,
                'stock_restored'      => $restoreStock,
            ]
        );

        return $result;
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
