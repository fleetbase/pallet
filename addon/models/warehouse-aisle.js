import { computed } from '@ember/object';
import { attr, belongsTo } from '@ember-data/model';
import { format as formatDate, isValid as isValidDate } from 'date-fns';

export default class WarehouseAisle extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') section_uuid;

    /** @relationships */
    @belongsTo('company', { async: true }) company;
    @belongsTo('user', { async: true }) createdBy;
    @belongsTo('pallet-warehouse-section', { async: true }) section;

    /** @attributes */
    @attr('string') aisle_number;
    @attr('polygon') area;
    @attr('raw') meta;

    /** @date */
    @attr('date') created_at;
    @attr('date') updated_at;

    /** @computed */
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
