<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Model;
use Fleetbase\Models\User;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audit extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_audits';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'audit';

    /**
     * The singularName overwrite.
     *
     * @var string
     */
    protected $singularName = 'audit';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['action', 'type', 'auditable_type', 'auditable_uuid'];

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
        'performed_by_uuid',
        'auditable_uuid',
        'auditable_type',
        'action',
        'type',
        'reason',
        'comments',
        'meta',
        'old_values',
        'new_values',
        'scheduled_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta'       => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * The relationships to be eager loaded.
     *
     * @var array
     */
    protected $with = ['performedBy'];

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
     * The dates that should be mutated to Carbon instances.
     *
     * @var array
     */
    protected $dates = [
        'scheduled_at',
        'completed_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the user who performed the audit action.
     *
     * @return BelongsTo
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_uuid');
    }

    /**
     * Get the user who created the audit record.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_uuid');
    }
}
