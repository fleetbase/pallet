<?php

namespace Fleetbase\Pallet\Models;

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
    protected $searchableColumns = ['uuid', 'product_uuid', 'warehouse_uuid', 'quantity', 'min_quantity', 'created_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'product_uuid',
        'warehouse_uuid',
        'batch_uuid',
        'quantity',
        'min_quantity',
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
                MAX(pallet_inventories.created_at) as latest_created_at,
                MAX(pallet_inventories.updated_at) as latest_updated_at,
                MAX(pallet_inventories.public_id) as latest_public_id,
                MAX(pallet_inventories.uuid) as latest_uuid,
                MAX(pallet_inventories.comments) as latest_comments,
                GROUP_CONCAT(DISTINCT pallet_batches.uuid) as batch_uuids,
                GROUP_CONCAT(DISTINCT pallet_batches.batch_number) as batch_numbers,
                SUM(pallet_inventories.quantity) as total_quantity
            ')
            ->leftJoin('pallet_batches', 'pallet_inventories.batch_uuid', '=', 'pallet_batches.uuid')
            ->groupBy('pallet_inventories.product_uuid');
    }
}
