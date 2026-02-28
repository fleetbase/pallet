<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Casts\Json;
use Fleetbase\Models\Model;
use Fleetbase\Models\User;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Audit — Immutable WMS Operational Audit Trail Record.
 *
 * This model represents a single entry in the Pallet operational audit trail.
 * It is distinct from the Spatie activity_log, which records low-level model
 * attribute changes automatically. The Audit model records high-level,
 * intentional warehouse business events such as:
 *
 *   - Stock adjustments (with reason codes)
 *   - Cycle count completions and approvals
 *   - Purchase order receipts
 *   - Sales order fulfilments
 *   - Stock transfers (initiated and completed)
 *   - Manual operational notes
 *
 * Audit records are NEVER created, updated, or deleted by users directly via
 * the API. They are written programmatically by the system via the AuditService
 * or the HasOperationalAuditTrail trait when a significant operational event occurs.
 *
 * The API exposes only read-only endpoints (index, show) for this resource.
 *
 * @property string $uuid
 * @property string $public_id
 * @property string $company_uuid
 * @property string $created_by_uuid
 * @property string $performed_by_uuid
 * @property string $auditable_uuid
 * @property string $auditable_type
 * @property string $event_type
 * @property string $action
 * @property string $type
 * @property string $reason
 * @property string $comments
 * @property array  $old_values
 * @property array  $new_values
 * @property array  $meta
 * @property \Carbon\Carbon $scheduled_at
 * @property \Carbon\Carbon $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
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
     * The singular name used for payload keys.
     *
     * @var string
     */
    protected $singularName = 'audit';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = [
        'action',
        'event_type',
        'type',
        'reason',
        'comments',
        'auditable_type',
        'auditable_uuid',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * Note: this model is written to programmatically via AuditService.
     * Direct user creation via the API is not permitted.
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
        'event_type',
        'action',
        'type',
        'reason',
        'comments',
        'old_values',
        'new_values',
        'meta',
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
        'meta'         => Json::class,
        'old_values'   => Json::class,
        'new_values'   => Json::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
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
    protected $appends = ['subject_label'];

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

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Get the user who performed the audited action.
     *
     * @return BelongsTo
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_uuid');
    }

    /**
     * Get the user who created the audit record (system user or API caller).
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_uuid');
    }

    /**
     * Get the polymorphic subject model that this audit event relates to.
     *
     * @return MorphTo
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'auditable_type', 'auditable_uuid');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Get a human-readable label for the auditable subject type.
     * Extracts the short class name from the fully-qualified auditable_type.
     *
     * @return string|null
     */
    public function getSubjectLabelAttribute(): ?string
    {
        if (!$this->auditable_type) {
            return null;
        }

        $parts = explode('\\', $this->auditable_type);

        return end($parts);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to filter by event type.
     *
     * @param Builder $query
     * @param string  $eventType
     * @return Builder
     */
    public function scopeOfEventType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by the auditable subject model.
     *
     * @param Builder $query
     * @param string  $uuid
     * @param string  $type
     * @return Builder
     */
    public function scopeForSubject(Builder $query, string $uuid, string $type): Builder
    {
        return $query->where('auditable_uuid', $uuid)
                     ->where('auditable_type', $type);
    }

    /**
     * Scope to filter by the user who performed the action.
     *
     * @param Builder $query
     * @param string  $userUuid
     * @return Builder
     */
    public function scopePerformedByUser(Builder $query, string $userUuid): Builder
    {
        return $query->where('performed_by_uuid', $userUuid);
    }

    /**
     * Scope to filter stock adjustment events.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeStockAdjustments(Builder $query): Builder
    {
        return $query->where('event_type', AuditEventType::STOCK_ADJUSTMENT);
    }

    /**
     * Scope to filter cycle count events.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCycleCounts(Builder $query): Builder
    {
        return $query->where('event_type', AuditEventType::CYCLE_COUNT);
    }

    /**
     * Scope to filter purchase order received events.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePurchaseOrders(Builder $query): Builder
    {
        return $query->where('event_type', AuditEventType::PO_RECEIVED);
    }

    /**
     * Scope to filter sales order fulfilled events.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSalesOrders(Builder $query): Builder
    {
        return $query->where('event_type', AuditEventType::SO_FULFILLED);
    }

    /**
     * Scope to filter stock transfer events.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeStockTransfers(Builder $query): Builder
    {
        return $query->where('event_type', AuditEventType::STOCK_TRANSFER);
    }
}
