<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Models\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseBin extends Model
{
    use HasUuid, HasApiModelBehavior, SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_warehouse_bins';

    /**
     * The singularName overwrite.
     *
     * @var string
     */
    protected $singularName = 'warehouse_bin';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['uuid', 'public_id', 'company_uuid', 'created_by_uuid', 'rack_uuid', 'bin_number', 'created_at'];

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
        'rack_uuid',
        'bin_number',
        'size',
        'max_weight',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'json',
    ];

    /**
     * Relationship with the company associated with the warehouse bin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the user who created the warehouse bin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the rack associated with the warehouse bin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rack()
    {
        return $this->belongsTo(PalletWarehouseRack::class, 'rack_uuid', 'uuid');
    }
}
