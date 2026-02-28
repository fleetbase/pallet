import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

export default class AuditModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') performed_by_uuid;
    @attr('string') created_by_uuid;
    @attr('string') auditable_uuid;
    @attr('string') auditable_type;

    /** @relationships */
    @belongsTo('user') performedBy;
    @belongsTo('user') createdBy;

    /** @attributes */
    @attr('string') action;
    @attr('string') type;
    @attr('string') reason;
    @attr('string') comments;
    @attr('raw') meta;
    @attr('raw') old_values;
    @attr('raw') new_values;

    /** @date */
    @attr('date') scheduled_at;
    @attr('date') completed_at;
    @attr('date') created_at;
    @attr('date') updated_at;

    /** @computed */
    @computed('created_at') get createdAgo() {
        if (!isValidDate(this.created_at)) {
            return null;
        }
        return formatDistanceToNow(this.created_at);
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

    @computed('auditable_type') get resourceLabel() {
        if (!this.auditable_type) {
            return null;
        }
        // Convert e.g. "Fleetbase\Pallet\Models\Inventory" -> "Inventory"
        const parts = this.auditable_type.split('\\');
        return parts[parts.length - 1];
    }
}
