<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;

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
     * @var array
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
     * @var array
     */
    protected $casts = [
        'meta'              => Json::class,
        'quantity'          => 'integer',
        'quantity_received' => 'integer',
    ];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['product', 'variant'];

    /**
     * Get the stock transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_uuid', 'uuid');
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
