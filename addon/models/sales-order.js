import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

export default class SalesOrderModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') supplier_uuid;
    @attr('string') created_by_uuid;
    @attr('string') transaction_uuid;
    @attr('string') assigned_to_uuid;
    @attr('string') point_of_contact_uuid;

    /** @relationships */
    @belongsTo('company') company;
    @belongsTo('user') createdBy;
    @belongsTo('transaction') transaction;
    @belongsTo('user') assignedTo;
    @belongsTo('vendor') supplier;
    @belongsTo('contact') pointOfContact;

    /** @attributes */
    @attr('string') status;
    @attr('string') customer_reference_code;
    @attr('string') reference_code;
    @attr('string') reference_url;
    @attr('string') description;
    @attr('string') comments;
    @attr('date') order_date_at;
    @attr('date') expected_delivery_at;
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

    @computed('updated_at') get updatedAgo() {
        if (!isValidDate(this.updated_at)) {
            return null;
        }
        return formatDistanceToNow(this.updated_at);
    }

    @computed('updated_at') get updatedAt() {
        if (!isValidDate(this.updated_at)) {
            return null;
        }
        return formatDate(this.updated_at, 'PPP p');
    }

    @computed('updated_at') get updatedAtShort() {
        if (!isValidDate(this.updated_at)) {
            return null;
        }
        return formatDate(this.updated_at, 'PP');
    }

    @computed('expected_delivery_at') get expectedDeliveryDate() {
        if (!isValidDate(this.expected_delivery_at)) {
            return null;
        }

        return formatDate(this.expected_delivery_at, 'yyyy-MM-dd');
    }
}
