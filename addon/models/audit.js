import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

/**
 * AuditModel represents a Pallet WMS operational audit event.
 *
 * This is NOT a generic CRUD log — that is handled by Spatie Activity Log.
 * This model captures intentional, business-significant warehouse events such as:
 *   - Stock adjustments
 *   - Cycle count completions / approvals
 *   - Purchase order receipts
 *   - Sales order fulfilments
 *   - Stock transfer completions
 *
 * The audit trail is immutable — records are created programmatically by WMS
 * models and services, never by direct user API calls.
 */
export default class AuditModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') performed_by_uuid;
    @attr('string') auditable_uuid;
    @attr('string') auditable_type;
    @attr('string') subject_uuid;
    @attr('string') subject_type;
    @attr('string') subject_label;

    /** @relationships */
    @belongsTo('user') performedBy;

    /** @attributes */
    @attr('string') event_type;
    @attr('string') action;
    @attr('string') type;
    @attr('string') reason;
    @attr('string') comments;
    @attr('raw') meta;

    /** @dates */
    @attr('date') scheduled_at;
    @attr('date') completed_at;
    @attr('date') created_at;
    @attr('date') updated_at;

    /** @computed */

    /**
     * Returns a human-readable label for the event type.
     * Maps the snake_case event_type constant to a display string.
     */
    @computed('event_type') get eventTypeLabel() {
        const labels = {
            stock_adjustment: 'Stock Adjustment',
            cycle_count: 'Cycle Count',
            po_received: 'PO Received',
            so_fulfilled: 'SO Fulfilled',
            stock_transfer: 'Stock Transfer',
            inventory_created: 'Inventory Received',
            batch_created: 'Batch Created',
        };
        return labels[this.event_type] || (this.event_type ? this.event_type.replace(/_/g, ' ') : '—');
    }

    /**
     * Returns the short class name of the subject model.
     * e.g. "Fleetbase\Pallet\Models\Inventory" → "Inventory"
     */
    @computed('subject_label', 'auditable_type', 'subject_type') get subjectLabel() {
        if (this.subject_label) {
            return this.subject_label;
        }

        const type = this.auditable_type || this.subject_type;
        if (!type) {
            return null;
        }
        const parts = type.split('\\');
        return parts[parts.length - 1];
    }

    /**
     * Returns a badge colour class based on the event type for use in the UI.
     */
    @computed('event_type') get eventTypeBadgeClass() {
        const colours = {
            stock_adjustment: 'bg-yellow-100 text-yellow-800',
            cycle_count: 'bg-blue-100 text-blue-800',
            po_received: 'bg-green-100 text-green-800',
            so_fulfilled: 'bg-purple-100 text-purple-800',
            stock_transfer: 'bg-indigo-100 text-indigo-800',
            inventory_created: 'bg-teal-100 text-teal-800',
        };
        return colours[this.event_type] || 'bg-gray-100 text-gray-800';
    }

    @computed('created_at') get createdAgo() {
        if (!isValidDate(this.created_at)) {
            return null;
        }
        return formatDistanceToNow(this.created_at, { addSuffix: true });
    }

    @computed('created_at') get createdAt() {
        if (!isValidDate(this.created_at)) {
            return null;
        }
        return formatDate(this.created_at, 'PPP p');
    }

    @computed('created_at') get createdAtShort() {
        if (!isValidDate(this.created_at)) {
            return null;
        }
        return formatDate(this.created_at, 'PP');
    }
}
