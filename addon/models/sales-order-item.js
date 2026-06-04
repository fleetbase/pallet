import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';

export default class SalesOrderItemModel extends Model {
    /** @ids */
    @attr('string') public_id;
    @attr('string') sales_order_uuid;
    @attr('string') product_uuid;
    @attr('string') variant_uuid;
    @attr('string') warehouse_uuid;
    @attr('string') inventory_uuid;

    /** @relationships */
    @belongsTo('pallet-product', { async: false }) product;
    @belongsTo('pallet-product-variant', { async: false }) variant;
    @belongsTo('pallet-warehouse', { async: false }) warehouse;
    @belongsTo('inventory', { async: false }) inventory;

    /** @quantities */
    @attr('number') quantity;
    @attr('number') quantity_fulfilled;
    @attr('number') outstanding_quantity;

    /** @pricing */
    @attr('string') currency;
    @attr('number') unit_price;
    @attr('number') total_price;

    /** @tracking */
    @attr('string') unit_of_measure;
    @attr('string') sku;
    @attr('string') lot_number;
    @attr('string') serial_number;

    /** @status */
    @attr('string') status;

    /** @meta */
    @attr('string') notes;
    @attr() meta;

    /** @dates */
    @attr('date') fulfilled_at;
    @attr('date') created_at;
    @attr('date') updated_at;

    /**
     * The display name for this line item — uses the product name if loaded,
     * falls back to the SKU, then the public_id.
     *
     * @type {String}
     */
    @computed('product.name', 'variant.display_name', 'sku', 'public_id')
    get displayName() {
        return this.variant?.display_name ?? this.product?.name ?? this.sku ?? this.public_id;
    }

    /**
     * Returns true when the item has been fully fulfilled.
     *
     * @type {Boolean}
     */
    @computed('quantity', 'quantity_fulfilled')
    get isFullyFulfilled() {
        return this.quantity_fulfilled >= this.quantity;
    }

    /**
     * Returns true when the item is only partially fulfilled.
     *
     * @type {Boolean}
     */
    @computed('status')
    get isPartial() {
        return this.status === 'partial';
    }
}
