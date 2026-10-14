import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate } from 'date-fns';

export default class InventoryReservationModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') product_uuid;
    @attr('string') variant_uuid;
    @attr('string') inventory_uuid;
    @attr('string') warehouse_uuid;
    @attr('string') order_uuid;
    @attr('string') sales_order_uuid;
    @attr('string') pick_list_uuid;
    @attr('string') storefront_product_uuid;
    @attr('string') storefront_variant_uuid;
    @attr('string') storefront_store_uuid;
    @attr('string') storefront_cart_uuid;
    @attr('string') storefront_checkout_uuid;
    @attr('string') storefront_order_uuid;
    @attr('string') storefront_line_uuid;
    @attr('string') storefront_reservation_key;
    @belongsTo('pallet-product', { async: false }) product;
    @belongsTo('pallet-product-variant', { async: false }) variant;
    @belongsTo('inventory', { async: false }) inventory;
    @belongsTo('warehouse', { async: false }) warehouse;
    @belongsTo('sales-order', { async: false }) salesOrder;
    @attr('number') quantity;
    @attr('date') reserved_at;
    @attr('date') expires_at;
    @attr('date') released_at;
    @attr('string') status;
    @attr('string') type;
    @attr('boolean') is_expired;
    @attr('boolean') is_active;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;

    /**
     * The list stacked checkout, cart, line and key on four lines in one cell, so
     * every row was four lines tall whether or not the reservation came from a
     * storefront at all. The key identifies the context on its own; the rest are
     * their own hidden columns for when someone needs them.
     */
    /**
     * Reserved and expires are dates the list has to show — §F puts both on this screen,
     * and an expiry is the only thing that makes a reservation need attention. Formatted
     * here as every other list in the module formats its dates, so a raw Date object
     * does not reach the cell.
     */
    @computed('reserved_at') get reservedAt() {
        if (!isValidDate(this.reserved_at)) {
            return null;
        }

        return formatDate(this.reserved_at, 'PP HH:mm');
    }

    @computed('expires_at') get expiresAt() {
        if (!isValidDate(this.expires_at)) {
            return null;
        }

        return formatDate(this.expires_at, 'PP HH:mm');
    }

    @computed('storefront_reservation_key', 'storefront_checkout_uuid', 'storefront_cart_uuid') get storefrontContextLabel() {
        return this.storefront_reservation_key ?? this.storefront_checkout_uuid ?? this.storefront_cart_uuid ?? null;
    }
}
