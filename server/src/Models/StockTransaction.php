<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Traits\HasUuid;

class StockTransaction extends Model
{
    use HasUuid;
    use HasApiModelBehavior;
    use HasMetaAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_stock_transactions';

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
    protected $searchableColumns = ['uuid', 'product_uuid', 'variant_uuid', 'transaction_type', 'quantity', 'transaction_date_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'public_id',
        'company_uuid',
        'created_by_uuid',
        'product_uuid',
        'variant_uuid',
        'batch_uuid',
        'transaction_type',
        'quantity',
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
     * @var array
     */
    protected $casts = [];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
