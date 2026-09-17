import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow, differenceInCalendarDays } from 'date-fns';

export default class BatchModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') product_uuid;
    @attr('string') variant_uuid;

    /** @relationships */
    @belongsTo('company') company;
    @belongsTo('user') createdBy;
    @belongsTo('pallet-product') product;
    @belongsTo('pallet-product-variant') variant;

    /** @attributes */
    @attr('string') batch_number;
    @attr('number') quantity;
    @attr('date') manufacture_date_at;
    @attr('date') expiry_date_at;
    @attr('date') created_at;
    @attr('date') updated_at;
    @attr('raw') meta;

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

    @computed('manufacture_date_at') get manufacturedAt() {
        if (!isValidDate(this.manufacture_date_at)) {
            return null;
        }

        return formatDate(this.manufacture_date_at, 'PP');
    }

    /**
     * Whole days until this batch expires. Negative once it has.
     */
    @computed('expiry_date_at') get daysToExpiry() {
        if (!isValidDate(this.expiry_date_at)) {
            return null;
        }

        return differenceInCalendarDays(this.expiry_date_at, new Date());
    }

    /**
     * `expired`, `expiring_soon`, or `ok`.
     *
     * Same 30-day window as the inventory model and the server's
     * scopeExpiringSoon($days = 30), so a batch and the stock held against it cannot
     * disagree about whether it is a problem.
     */
    @computed('daysToExpiry') get expiryStatus() {
        const days = this.daysToExpiry;

        if (days === null) {
            return null;
        }

        if (days < 0) {
            return 'expired';
        }

        return days <= 30 ? 'expiring_soon' : 'ok';
    }

    @computed('expiry_date_at') get expiresAt() {
        if (!isValidDate(this.expiry_date_at)) {
            return null;
        }

        return formatDate(this.expiry_date_at, 'PP');
    }
}
