import Model, { attr } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

export default class SalesOrderModel extends Model {
    /** @ids */
    @attr('string') uuid;

    /** @attributes */
    @attr('string') customer_uuid;
    @attr('string') status;

    /** @dates */
    @attr('date') order_created_at;
    @attr('date') delivered_at;
    @attr('date') created_at;
    @attr('date') updated_at;
    @attr('date') deleted_at;

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

    @computed('order_created_at') get orderCreatedAgo() {
        if (!isValidDate(this.order_created_at)) {
            return null;
        }
        return formatDistanceToNow(this.order_created_at);
    }

    @computed('order_created_at') get orderCreatedAt() {
        if (!isValidDate(this.order_created_at)) {
            return null;
        }
        return formatDate(this.order_created_at, 'PPP p');
    }

    @computed('order_created_at') get orderCreatedAtShort() {
        if (!isValidDate(this.order_created_at)) {
            return null;
        }
        return formatDate(this.order_created_at, 'PP');
    }
}
