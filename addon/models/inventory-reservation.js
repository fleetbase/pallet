import Model, { attr, belongsTo } from '@ember-data/model';

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
}
