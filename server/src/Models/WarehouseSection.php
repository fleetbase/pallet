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
 * @property ?int                                                          $id
 * @property string                                                        $uuid
 * @property ?string                                                       $public_id
 * @property ?string                                                       $company_uuid
 * @property ?string                                                       $created_by_uuid
 * @property ?string                                                       $warehouse_uuid
 * @property ?string                                                       $name
 * @property ?string                                                       $description
 * @property ?array                                                        $area
 * @property ?array                                                        $meta
 * @property ?\Illuminate\Support\Carbon                                   $created_at
 * @property ?\Illuminate\Support\Carbon                                   $updated_at
 * @property ?\Illuminate\Support\Carbon                                   $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<int, WarehouseAisle> $aisles
 * @property \Fleetbase\Pallet\Models\Company|null                         $company
 * @property \Fleetbase\Pallet\Models\User|null                            $createdBy
 * @property Warehouse|null                                                $warehouse
 */
class WarehouseSection extends Model
{
    use HasUuid;
    use HasPublicId;
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

    /**
     * @var array<int, string>
     */
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

    /**
     * @var array<string, string>
     */
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
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_uuid', 'uuid');
    }

    /**
     * Relationship with the company associated with the warehouse rack.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_uuid', 'uuid');
    }

    /**
     * Relationship with the company associated with the warehouse rack.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_uuid', 'uuid');
    }

    public function aisles(): HasMany
    {
        return $this->hasMany(WarehouseAisle::class, 'section_uuid', 'uuid');
    }
}
