import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';

export default class PurchaseOrderItemModel extends Model {
    /** @ids */
    @attr('string') public_id;
    @attr('string') purchase_order_uuid;
    @attr('string') product_uuid;
    @attr('string') warehouse_uuid;

    /** @relationships */
    @belongsTo('pallet-product', { async: false }) product;
    @belongsTo('pallet-warehouse', { async: false }) warehouse;

    /** @quantities */
    @attr('number') quantity;
    @attr('number') quantity_received;
    @attr('number') outstanding_quantity;

    /** @pricing */
    @attr('string') currency;
    @attr('number') unit_price;
    @attr('number') unit_cost;
    @attr('number') total_price;

    /** @tracking */
    @attr('string') unit_of_measure;
    @attr('string') sku;
    @attr('string') lot_number;
    @attr('string') serial_number;
    @attr('date') expiry_date;

    /** @status */
    @attr('string') status;

    /** @meta */
    @attr('string') notes;
    @attr() meta;

    /** @dates */
    @attr('date') received_at;
    @attr('date') created_at;
    @attr('date') updated_at;

    /**
     * The display name for this line item — uses the product name if loaded,
     * falls back to the SKU, then the public_id.
     *
     * @type {String}
     */
    @computed('product.name', 'sku', 'public_id')
    get displayName() {
        return this.product?.name ?? this.sku ?? this.public_id;
    }

    /**
     * Returns true when the item has been fully received.
     *
     * @type {Boolean}
     */
    @computed('quantity', 'quantity_received')
    get isFullyReceived() {
        return this.quantity_received >= this.quantity;
    }

    /**
     * Returns true when the item is only partially received.
     *
     * @type {Boolean}
     */
    @computed('status')
    get isPartial() {
        return this.status === 'partial';
    }
}
