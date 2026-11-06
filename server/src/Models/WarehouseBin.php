<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Company;
use Fleetbase\Models\Model;
use Fleetbase\Models\User;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                                  $id
 * @property string                                $uuid
 * @property ?string                               $public_id
 * @property ?string                               $company_uuid
 * @property ?string                               $created_by_uuid
 * @property ?string                               $warehouse_uuid
 * @property ?string                               $rack_uuid
 * @property ?string                               $bin_number
 * @property ?string                               $size
 * @property ?string                               $max_weight
 * @property ?array                                $meta
 * @property ?\Illuminate\Support\Carbon           $created_at
 * @property ?\Illuminate\Support\Carbon           $updated_at
 * @property ?\Illuminate\Support\Carbon           $deleted_at
 * @property \Fleetbase\Pallet\Models\Company|null $company
 * @property \Fleetbase\Pallet\Models\User|null    $createdBy
 * @property WarehouseRack|null                    $racks
 */
class WarehouseBin extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;

    /**
     * Overwrite both place resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'warehouse_bin';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'warehouse_bin';

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
     * @var array<int, string>
     */
    protected $fillable = [
        'warehouse_uuid',
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
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'json',
    ];

    /**
     * Relationship with the company associated with the warehouse bin.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the user who created the warehouse bin.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the rack associated with the warehouse bin.
     */
    public function racks(): BelongsTo
    {
        return $this->belongsTo(WarehouseRack::class, 'rack_uuid', 'uuid');
    }
}
