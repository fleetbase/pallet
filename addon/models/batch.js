import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

export default class BatchModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') product_uuid;

    /** @relationships */
    @belongsTo('company') company;
    @belongsTo('user') createdBy;
    @belongsTo('pallet-product') product;

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
}
