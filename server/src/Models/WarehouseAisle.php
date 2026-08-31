<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Company;
use Fleetbase\Models\Model;
use Fleetbase\Models\User;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                                                         $id
 * @property string                                                       $uuid
 * @property ?string                                                      $public_id
 * @property ?string                                                      $company_uuid
 * @property ?string                                                      $created_by_uuid
 * @property ?string                                                      $warehouse_uuid
 * @property ?string                                                      $zone_uuid
 * @property ?string                                                      $section_uuid
 * @property ?string                                                      $aisle_number
 * @property ?array                                                       $area
 * @property ?array                                                       $meta
 * @property ?\Illuminate\Support\Carbon                                  $created_at
 * @property ?\Illuminate\Support\Carbon                                  $updated_at
 * @property ?\Illuminate\Support\Carbon                                  $deleted_at
 * @property \Fleetbase\Pallet\Models\Company|null                        $company
 * @property \Fleetbase\Pallet\Models\User|null                           $createdBy
 * @property \Illuminate\Database\Eloquent\Collection<int, WarehouseRack> $racks
 * @property WarehouseSection|null                                        $section
 */
class WarehouseAisle extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;

    /**
     * Overwrite both place resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'warehouse_aisle';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'warehouse_aisle';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_warehouse_aisles';

    /**
     * The singularName overwrite.
     *
     * @var string
     */
    protected $singularName = 'warehouse_aisle';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['uuid', 'public_id', 'company_uuid', 'created_by_uuid', 'section_uuid', 'aisle_number', 'created_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'zone_uuid',
        'warehouse_uuid',
        'uuid',
        'public_id',
        'company_uuid',
        'created_by_uuid',
        'section_uuid',
        'aisle_number',
        'area',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'area' => 'json',
        'meta' => 'json',
    ];

    /**
     * Relationship with the company associated with the warehouse aisle.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the user who created the warehouse aisle.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the section associated with the warehouse aisle.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(WarehouseSection::class, 'section_uuid', 'uuid');
    }

    public function racks(): HasMany
    {
        return $this->hasMany(WarehouseRack::class, 'aisle_uuid', 'uuid');
    }
}
