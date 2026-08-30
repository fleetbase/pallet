import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow, differenceInCalendarDays } from 'date-fns';

export default class InventoryModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') product_uuid;
    @attr('string') variant_uuid;
    @attr('string') warehouse_uuid;
    @attr('string') supplier_uuid;
    @attr('string') batch_uuid;
    @attr('string') bin_location_uuid;
    @attr('string') zone_uuid;

    /** @relationships */
    @belongsTo('company') company;
    @belongsTo('user') createdBy;
    @belongsTo('pallet-product') product;
    @belongsTo('pallet-product-variant') variant;
    @belongsTo('warehouse') warehouse;
    @belongsTo('supplier') supplier;
    @belongsTo('batch') batch;
    @belongsTo('warehouse-zone') zone;
    @belongsTo('bin-location') binLocation;

    /** @attributes */
    @attr('string') status;
    @attr('string') lot_number;
    @attr('string') serial_number;
    @attr('string') uom;
    @attr('string') comments;
    @attr('number') quantity;
    @attr('number') reserved_quantity;
    @attr('number') available_quantity;
    /*
     * The three slots that complete the quantity set. A warehouse quantity is a set,
     * not a number — on-hand alone answers "what is on the shelf" but not "can I
     * promise this". available_quantity deliberately stays on-hand minus reserved;
     * quarantined is tracked but not deducted, because changing that formula changes
     * stock maths the reserve/commit chain is tested against.
     */
    @attr('number') in_transit;
    @attr('number') on_order;
    @attr('number') quarantined;
    @attr('number') min_quantity;
    @attr('number') max_quantity;
    @attr('number') reorder_point;
    @attr('number') unit_cost;
    @attr('raw') meta;

    /** @date */
    @attr('date') created_at;
    @attr('date') updated_at;
    @attr('date') expiry_date_at;
    @attr('date') manufactured_date_at;
    @attr('date') received_at;
    @attr('date') last_counted_at;

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

    /**
     * How far below its minimum this row is sitting, in units.
     *
     * The low-stock screen is a worklist, and "quantity 3, min 5" makes a reader do
     * the subtraction on every row to find the worst one. The server orders by this
     * same expression, so the column and the sort agree.
     */
    @computed('min_quantity', 'available_quantity') get shortBy() {
        const min = Number(this.min_quantity ?? 0);
        const available = Number(this.available_quantity ?? 0);

        if (min <= 0) {
            return null;
        }

        return Math.max(0, min - available);
    }

    /**
     * Whole days since this stock expired. Null when it has not.
     */
    @computed('expiry_date_at') get daysOverExpiry() {
        if (!isValidDate(this.expiry_date_at)) {
            return null;
        }

        const days = differenceInCalendarDays(new Date(), this.expiry_date_at);

        return days > 0 ? days : null;
    }

    @computed('received_at') get receivedAt() {
        if (!isValidDate(this.received_at)) {
            return null;
        }
        return formatDate(this.received_at, 'PPP p');
    }

    @computed('last_counted_at') get lastCountedAt() {
        if (!isValidDate(this.last_counted_at)) {
            return null;
        }
        return formatDate(this.last_counted_at, 'PPP p');
    }

    @computed('quantity', 'min_quantity') get isLowStock() {
        return this.quantity !== null && this.min_quantity !== null && this.quantity <= this.min_quantity;
    }

    @computed('expiry_date_at') get isExpired() {
        if (!isValidDate(this.expiry_date_at)) {
            return false;
        }
        return new Date() > this.expiry_date_at;
    }
}
