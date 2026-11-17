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
 * @property ?string                     $stock_transfer_uuid
 * @property ?string                     $product_uuid
 * @property ?string                     $variant_uuid
 * @property ?int                        $quantity
 * @property ?int                        $quantity_received
 * @property ?string                     $lot_number
 * @property ?string                     $serial_number
 * @property ?string                     $notes
 * @property ?array                      $meta
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property Product|null                $product
 * @property StockTransfer|null          $stockTransfer
 * @property ProductVariant|null         $variant
 */
class StockTransferItem extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_stock_transfer_items';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'stock_transfer_item';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'transfer_item';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_uuid',
        'stock_transfer_uuid',
        'product_uuid',
        'variant_uuid',
        'quantity',
        'quantity_received',
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
        'quantity'          => 'integer',
        'quantity_received' => 'integer',
    ];

    /**
     * Relationships to eager load.
     *
     * @var array<int, string>
     */
    protected $with = ['product', 'variant'];

    /**
     * Get the stock transfer.
     */
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_uuid', 'uuid');
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
     * Record received quantity.
     *
     * @param int $quantity
     *
     * @return bool
     */
    public function recordReceived($quantity)
    {
        $this->quantity_received = $quantity;

        return $this->save();
    }
}
