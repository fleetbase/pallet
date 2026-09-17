import Model, { attr, belongsTo, hasMany } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate } from 'date-fns';

export default class StockTransferModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') from_warehouse_uuid;
    @attr('string') to_warehouse_uuid;
    @belongsTo('warehouse', { async: false, inverse: null }) fromWarehouse;
    @belongsTo('warehouse', { async: false, inverse: null }) toWarehouse;
    @hasMany('stock-transfer-item', { async: false }) items;
    @attr('string') transfer_number;
    @attr('string') status;
    @attr('string') type;
    @attr('string') requested_by_uuid;
    @attr('string') approved_by_uuid;
    @attr('date') shipped_at;
    @attr('date') received_at;
    @attr('number') total_items;
    @attr('number') total_quantity;
    @attr('string') notes;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;

    /**
     * The list used to render one line per item, so a transfer of four products
     * was a four-line row. One product reads as itself; more than one reads as a
     * count, and the detail is a click away.
     */
    @computed('items.@each.product') get itemsSummary() {
        const items = this.items ?? [];
        const count = items.length;

        if (count === 0) {
            return null;
        }

        if (count === 1) {
            return items[0]?.product?.name ?? null;
        }

        return `${count} products`;
    }

    @computed('created_at') get createdAt() {
        if (!isValidDate(this.created_at)) {
            return null;
        }

        return formatDate(this.created_at, 'PP HH:mm');
    }
}
