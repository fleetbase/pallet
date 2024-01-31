import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

export default class InventoryModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') product_uuid;
    @attr('string') warehouse_uuid;
    @attr('string') supplier_uuid;
    @attr('string') batch_uuid;

    /** @relationships */
    @belongsTo('company') company;
    @belongsTo('user') createdBy;
    @belongsTo('pallet-product') product;
    @belongsTo('warehouse') warehouse;
    @belongsTo('vendor') supplier;
    @belongsTo('batch') batch;

    /** @attributes */
    @attr('string') comments;
    @attr('number') quantity;
    @attr('number') min_quantity;
    @attr('number') status;
    @attr('raw') meta;

    /** @date */
    @attr('date') created_at;
    @attr('date') updated_at;
    @attr('date') expiry_date_at;
    @attr('date') manufactured_date_at;

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

    @computed('expiry_date_at') get expiryDate() {
        if (!isValidDate(this.expiry_date_at)) {
            return null;
        }

        return formatDate(this.expiry_date_at, 'yyyy-MM-dd');
    }
}
