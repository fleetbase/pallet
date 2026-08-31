<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                           $id
 * @property string                         $uuid
 * @property ?string                        $public_id
 * @property ?string                        $company_uuid
 * @property ?string                        $created_by_uuid
 * @property ?string                        $product_uuid
 * @property ?string                        $variant_uuid
 * @property ?string                        $destination_uuid
 * @property ?string                        $batch_uuid
 * @property ?string                        $inventory_uuid
 * @property ?string                        $source_uuid
 * @property ?string                        $source_type
 * @property ?array                         $meta
 * @property ?string                        $transaction_type
 * @property ?int                           $quantity
 * @property ?int                           $balance_after
 * @property ?\Illuminate\Support\Carbon    $transaction_date_at
 * @property ?\Illuminate\Support\Carbon    $transaction_created_at
 * @property ?\Illuminate\Support\Carbon    $created_at
 * @property ?\Illuminate\Support\Carbon    $updated_at
 * @property ?\Illuminate\Support\Carbon    $deleted_at
 * @property Batch|null                     $batch
 * @property \Fleetbase\Models\Company|null $company
 * @property \Fleetbase\Models\User|null    $createdBy
 * @property Inventory|null                 $inventory
 * @property Product|null                   $product
 * @property ProductVariant|null            $variant
 * @property Warehouse|null                 $warehouse
 */
class StockTransaction extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use HasMetaAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_stock_transactions';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'stock_transaction';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'stock_txn';

    /**
     * The singularName overwrite.
     *
     * @var string
     */
    protected $singularName = 'stock-transaction';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['uuid', 'public_id', 'product_uuid', 'variant_uuid', 'inventory_uuid', 'transaction_type', 'quantity', 'transaction_date_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'public_id',
        'company_uuid',
        'created_by_uuid',
        'product_uuid',
        'variant_uuid',
        'batch_uuid',
        'inventory_uuid',
        'transaction_type',
        'quantity',
        'balance_after',
        'transaction_date_at',
        'source_uuid',
        'source_type',
        'destination_uuid',
        'meta',
        'transaction_created_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta'                   => Json::class,
        'quantity'               => 'integer',
        'balance_after'          => 'integer',
        'transaction_date_at'    => 'datetime',
        'transaction_created_at' => 'datetime',
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array<int, string>
     */
    protected $appends = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Fleetbase\Models\Company::class, 'company_uuid', 'uuid');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Fleetbase\Models\User::class, 'created_by_uuid', 'uuid');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_uuid', 'uuid');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_uuid', 'uuid');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_uuid', 'uuid');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_uuid', 'uuid');
    }
}
