<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Models\Model;

class Batch extends Model
{
    use HasUuid, HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_batches';

    /**
     * Overwrite both entity resource name with `payloadKey`
     *
     * @var string
     */
    protected $payloadKey = 'batch';

    /**
     * The type of `public_id` to generate
     *
     * @var string
     */
    protected $publicIdType = 'batch';

    /**
     * These attributes that can be queried
     *
     * @var array
     */
    protected $searchableColumns = ['uuid', 'batch_number', 'product_uuid', 'manufacture_date_at', 'expiry_date_at', 'quantity', 'created_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'batch_number',
        'product_uuid',
        'manufacture_date_at',
        'expiry_date_at',
        'quantity',
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
     * Dynamic attributes that are appended to object
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
     * Relationship with the company associated with the batch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the user who created the batch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the product associated with the batch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
