<?php

namespace Fleetbase\Pallet\Traits;

use Fleetbase\Pallet\Models\Audit;
use Fleetbase\Pallet\Models\AuditEventType;
use Illuminate\Support\Facades\Auth;

/**
 * HasOperationalAuditTrail.
 *
 * A trait that any Pallet model can use to programmatically write entries to
 * the pallet_audits operational audit trail.
 *
 * This is distinct from the Spatie LogsActivity trait, which automatically
 * records low-level model attribute changes (created, updated, deleted).
 * This trait is for intentional, high-level warehouse business events.
 *
 * Usage:
 *   // In a model or controller:
 *   $stockAdjustment->logAuditEvent(
 *       AuditEventType::STOCK_ADJUSTMENT,
 *       'Stock Adjusted',
 *       'damage',
 *       $stockAdjustment->reason,
 *       ['before' => $before, 'after' => $after]
 *   );
 */
trait HasOperationalAuditTrail
{
    /**
     * Log an operational audit event for this model instance.
     *
     * @param string      $eventType Machine-readable event category (use AuditEventType constants)
     * @param string      $action    Human-readable label for the event (e.g. "Stock Adjusted")
     * @param string|null $type      Secondary classification within the event type (e.g. "damage")
     * @param string|null $reason    Reason code or user-supplied explanation
     * @param array       $meta      Arbitrary structured context data
     * @param array       $oldValues Snapshot of relevant values before the event
     * @param array       $newValues Snapshot of relevant values after the event
     * @param string|null $comments  Free-form notes
     */
    public function logAuditEvent(
        string $eventType,
        string $action,
        ?string $type = null,
        ?string $reason = null,
        array $meta = [],
        array $oldValues = [],
        array $newValues = [],
        ?string $comments = null,
    ): Audit {
        $performedByUuid = Auth::id() ?? null;

        return Audit::create([
            'company_uuid'      => $this->company_uuid ?? null,
            'created_by_uuid'   => $performedByUuid,
            'performed_by_uuid' => $performedByUuid,
            'auditable_uuid'    => $this->uuid,
            'auditable_type'    => get_class($this),
            'event_type'        => $eventType,
            'action'            => $action,
            'type'              => $type,
            'reason'            => $reason,
            'comments'          => $comments,
            'old_values'        => $oldValues ?: null,
            'new_values'        => $newValues ?: null,
            'meta'              => $meta ?: null,
            'completed_at'      => now(),
        ]);
    }

    /**
     * Log a scheduled operational audit event for this model instance.
     * Used for events that have a planned execution time (e.g. cycle counts).
     *
     * @param string         $eventType   Machine-readable event category
     * @param string         $action      Human-readable label for the event
     * @param \Carbon\Carbon $scheduledAt When the event is scheduled to occur
     * @param string|null    $type        Secondary classification
     * @param string|null    $reason      Reason code or explanation
     * @param array          $meta        Arbitrary structured context data
     */
    public function logScheduledAuditEvent(
        string $eventType,
        string $action,
        \Carbon\Carbon $scheduledAt,
        ?string $type = null,
        ?string $reason = null,
        array $meta = [],
    ): Audit {
        $performedByUuid = Auth::id() ?? null;

        return Audit::create([
            'company_uuid'      => $this->company_uuid ?? null,
            'created_by_uuid'   => $performedByUuid,
            'performed_by_uuid' => $performedByUuid,
            'auditable_uuid'    => $this->uuid,
            'auditable_type'    => get_class($this),
            'event_type'        => $eventType,
            'action'            => $action,
            'type'              => $type,
            'reason'            => $reason,
            'meta'              => $meta ?: null,
            'scheduled_at'      => $scheduledAt,
        ]);
    }

    /**
     * Retrieve all audit trail entries for this model instance.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function auditTrail()
    {
        return Audit::forSubject($this->uuid, get_class($this))
                    ->orderBy('created_at', 'desc')
                    ->get();
    }
}
