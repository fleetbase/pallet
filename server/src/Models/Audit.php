<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audit extends Model
{
    use HasUuid;
    use HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_audits';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

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
    protected $searchableColumns = ['uuid', 'user_uuid', 'action', 'auditable_type', 'auditable_uuid', 'created_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'user_uuid',
        'action',
        'auditable_type',
        'auditable_uuid',
        'old_values',
        'new_values',
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
     * The relationships to be eager loaded.
     *
     * @var array
     */
    protected $with = ['user'];

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
     * Get the user that performed the audit.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_uuid');
    }
}
