import Model, { attr, belongsTo } from '@ember-data/model';

export default class InventoryReservationModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') product_uuid;
    @attr('string') variant_uuid;
    @attr('string') inventory_uuid;
    @attr('string') warehouse_uuid;
    @attr('string') sales_order_uuid;
    @attr('string') pick_list_uuid;
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
