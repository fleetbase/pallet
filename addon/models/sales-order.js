import Model, { attr } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

export default class SalesOrderModel extends Model {
    /** @ids */
    @attr('string') uuid;

    /** @attributes */
    @attr('string') customerUuid;
    @attr('string') status;

    /** @dates */
    @attr('date') orderCreatedAt;
    @attr('date') deliveredAt;
    @attr('date') createdAt;
    @attr('date') updatedAt;
    @attr('date') deletedAt;

    @computed('createdAt') get createdAgo() {
        return this.formatDateDistanceToNow(this.createdAt);
    }

    @computed('createdAt') get createdAtFormatted() {
        return this.formatDate(this.createdAt, 'PPP p');
    }

    @computed('createdAt') get createdAtShort() {
        return this.formatDate(this.createdAt, 'PP');
    }

    @computed('orderCreatedAt') get orderCreatedAgo() {
        return this.formatDateDistanceToNow(this.orderCreatedAt);
    }

    @computed('orderCreatedAt') get orderCreatedAtFormatted() {
        return this.formatDate(this.orderCreatedAt, 'PPP p');
    }

    @computed('orderCreatedAt') get orderCreatedAtShort() {
        return this.formatDate(this.orderCreatedAt, 'PP');
    }

    @computed('deliveredAt') get deliveredAtShort() {
        return this.formatDateDistanceToNow(this.deliveredAt);
    }

    @computed('deliveredAt') get deliveredAtFormatted() {
        return this.formatDate(this.deliveredAt, 'PPP p');
    }

    formatDate(date, formatString) {
        if (!isValidDate(date)) {
            return null;
        }
        return formatDate(date, formatString);
    }

    formatDateDistanceToNow(date) {
        if (!isValidDate(date)) {
            return null;
        }
        return formatDistanceToNow(date);
    }
}
