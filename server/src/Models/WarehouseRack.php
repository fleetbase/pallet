<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseRack extends Model
{
    use HasUuid;
    use HasApiModelBehavior;
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_warehouse_racks';

    /**
     * The singularName overwrite.
     *
     * @var string
     */
    protected $singularName = 'warehouse_rack';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['uuid', 'public_id', 'company_uuid', 'created_by_uuid', 'aisle_uuid', 'rack_number', 'created_at'];

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
        'aisle_uuid',
        'rack_number',
        'capacity',
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
     * Relationship with the company associated with the warehouse rack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the user who created the warehouse rack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the aisle associated with the warehouse rack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function aisle()
    {
        return $this->belongsTo(WarehouseAisle::class, 'aisle_uuid', 'uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bins()
    {
        return $this->hasMany(WarehouseBin::class, 'rack_uuid');
    }
}
