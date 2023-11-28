import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

export default class PurchaseOrderModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') supplier_uuid;
    @attr('string') transaction_uuid;
    @attr('string') assigned_to_uuid;
    @attr('string') point_of_contact_uuid;

    /** @relationships */
    @belongsTo('company') company;
    @belongsTo('user') createdBy;
    @belongsTo('vendor') supplier;
    @belongsTo('transaction') transaction;
    @belongsTo('user') assignedTo;
    @belongsTo('contact') pointOfContact;

    /** @attributes */
    @attr('string') reference_code;
    @attr('string') reference_url;
    @attr('string') description;
    @attr('string') comments;
    @attr('string') currency;
    @attr('string') status;

    /** @date */
    @attr('date') order_created_at;
    @attr('date') expected_delivery_at;
    @attr('date') created_at;
    @attr('date') updated_at;

    /** @boolean */
    @attr('boolean') isDeleted;

    /** @computed */
    @computed('order_created_at') get orderCreatedAt() {
        if (!isValidDate(this.order_created_at)) {
            return null;
        }
        return formatDate(this.order_created_at, 'PPP p');
    }

    @computed('expected_delivery_at') get expectedDeliveryAt() {
        if (!isValidDate(this.expected_delivery_at)) {
            return null;
        }
        return formatDate(this.expected_delivery_at, 'PPP p');
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
}
