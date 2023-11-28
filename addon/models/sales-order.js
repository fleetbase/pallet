import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

export default class SalesOrderModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') transaction_uuid;
    @attr('string') assigned_to_uuid;
    @attr('string') point_of_contact_uuid;
    @attr('string') customer_uuid;

    /** @relationships */
    @belongsTo('company') company;
    @belongsTo('user') createdBy;
    @belongsTo('transaction') transaction;
    @belongsTo('user') assignedTo;
    @belongsTo('contact') pointOfContact;
    @belongsTo('contact') customer;

    /** @attributes */
    @attr('string') customer_type;
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
