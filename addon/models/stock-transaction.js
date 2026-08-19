import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

export default class StockTransactionModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') product_uuid;
    @attr('string') variant_uuid;
    @attr('string') batch_uuid;
    @attr('string') inventory_uuid;
    @attr('string') source_uuid;
    @attr('string') source_type;
    @attr('string') destination_uuid;

    /** @relationships */
    @belongsTo('company') company;
    @belongsTo('user') createdBy;
    @belongsTo('pallet-product') product;
    @belongsTo('pallet-product-variant') variant;
    @belongsTo('batch') batch;
    @belongsTo('inventory') inventory;
    @belongsTo('warehouse') warehouse;

    /** @attributes */
    @attr('string') transaction_type;
    @attr('number') quantity;
    @attr('raw') meta;

    /** @dates */
    @attr('date') transaction_date_at;
    @attr('date') transaction_created_at;
    @attr('date') created_at;
    @attr('date') updated_at;

    /** @computed */
    @computed('transaction_type') get typeLabel() {
        if (!this.transaction_type) {
            return null;
        }

        return this.transaction_type.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
    }

    @computed('transaction_type') get isInbound() {
        return ['received', 'transferred', 'returned', 'transfer_cancelled'].includes(this.transaction_type);
    }

    @computed('quantity', 'isInbound') get signedQuantity() {
        const quantity = Math.abs(this.quantity ?? 0);

        return this.isInbound ? quantity : -quantity;
    }

    @computed('transaction_date_at', 'created_at') get transactionDate() {
        const date = this.transaction_date_at ?? this.created_at;

        if (!isValidDate(date)) {
            return null;
        }

        return formatDate(date, 'PPP p');
    }

    @computed('transaction_date_at', 'created_at') get transactionDateShort() {
        const date = this.transaction_date_at ?? this.created_at;

        if (!isValidDate(date)) {
            return null;
        }

        return formatDate(date, 'PP');
    }

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

    @computed('updated_at') get updatedAt() {
        if (!isValidDate(this.updated_at)) {
            return null;
        }

        return formatDate(this.updated_at, 'PPP p');
    }
}
