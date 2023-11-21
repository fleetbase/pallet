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
    @belongsTo('company', { async: true }) company;
    @belongsTo('user', { async: true }) createdBy;
    @belongsTo('pallet-product', { async: true }) product;
    @belongsTo('warehouse', { async: true }) warehouse;
    @belongsTo('vendor', { async: true }) supplier;
    @belongsTo('batch', { async: true }) batch;

    /** @attributes */
    @attr('string') comments;
    @attr('number') quantity;
    @attr('number') min_quantity;

    /** @date */
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
