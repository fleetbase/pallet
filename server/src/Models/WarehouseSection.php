<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasUuid;

class WarehouseSection extends Model
{
    use HasUuid;
    use HasApiModelBehavior;

    /**
     * Overwrite both place resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'warehouse_section';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_warehouse_sections';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'warehouse_section';

    protected $fillable = [
        'uuid',
        'public_id',
        'company_uuid',
        'created_by_uuid',
        'warehouse_uuid',
        'name',
        'description',
        'area',
        'meta',
    ];

    protected $casts = [
        'area' => 'json',
        'meta' => 'json',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
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
     * Relationship with the company associated with the warehouse rack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the company associated with the warehouse rack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Place::class, 'warehouse_uuid', 'uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function aisles()
    {
        return $this->hasMany(WarehouseAisle::class, 'section_uuid');
    }
}
