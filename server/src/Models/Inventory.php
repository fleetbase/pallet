<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;

class Inventory extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_inventories';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'inventory';

    /**
     * The type of `public_id` to generate.
     *
     * @var string
     */
    protected $publicIdType = 'inventory';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['product.name', 'warehouse.address', 'comments'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'supplier',
        'supplier_uuid',
        'company_uuid',
        'created_by_uuid',
        'manufactured_date_at',
        'expiry_date_at',
        'created_at',
        'updated_at',
        'product_uuid',
        'warehouse_uuid',
        'batch_uuid',
        'quantity',
        'min_quantity',
        'comments',
        'status',
    ];

    public $timestamps = true;

    protected $dates = ['expiry_date_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => Json::class,
    ];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = ['incrementing_id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    protected $with = ['product', 'batch', 'warehouse', 'supplier'];

    protected $filterParams = ['comments', 'expiry_date_at', 'status', 'company', 'createdBy',];

    /**
     * @return null|int
     */
    public function getIncrementingIdAttribute(): ?int
    {
        return static::select('id')->where('uuid', $this->uuid)->value('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_uuid', 'uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(\Fleetbase\FleetOps\Models\Place::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Undocumented function.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSummarizeByProduct($query)
    {
        return $query
            ->selectRaw('
                pallet_inventories.product_uuid,
                pallet_inventories.batch_uuid,
                pallet_inventories.supplier_uuid,
                pallet_inventories.warehouse_uuid,
                MAX(pallet_inventories.created_at) as latest_created_at,
                MAX(pallet_inventories.updated_at) as latest_updated_at,
                MAX(pallet_inventories.public_id) as latest_public_id,
                MAX(pallet_inventories.uuid) as latest_uuid,
                MAX(pallet_inventories.comments) as latest_comments,
                (SELECT GROUP_CONCAT(DISTINCT pallet_batches.uuid) FROM pallet_batches WHERE pallet_batches.uuid = pallet_inventories.batch_uuid) as batch_uuids,
                (SELECT GROUP_CONCAT(DISTINCT pallet_batches.batch_number) FROM pallet_batches WHERE pallet_batches.uuid = pallet_inventories.batch_uuid) as batch_numbers,
                SUM(pallet_inventories.quantity) as total_quantity,
                MAX(pallet_inventories.min_quantity) as minimum_quantity,
                MAX(pallet_inventories.expiry_date_at) as latest_expiry_date_at
            ')
            ->leftJoin('pallet_batches', 'pallet_inventories.batch_uuid', '=', 'pallet_batches.uuid')
            ->groupBy('pallet_inventories.product_uuid', 'pallet_inventories.batch_uuid', 'pallet_inventories.supplier_uuid', 'pallet_inventories.warehouse_uuid');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();
        });
    }
}
