import Model, { attr, belongsTo } from '@ember-data/model';

export default class PickListItemModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') pick_list_uuid;
    @attr('string') product_uuid;
    @attr('string') variant_uuid;
    @attr('string') inventory_uuid;
    @attr('string') bin_location_uuid;
    @attr('string') sales_order_item_uuid;
    @belongsTo('pallet-product', { async: false }) product;
    @belongsTo('pallet-product-variant', { async: false }) variant;
    @belongsTo('inventory', { async: false }) inventory;
    @belongsTo('bin-location', { async: false }) binLocation;
    @attr('number') quantity_requested;
    @attr('number') quantity_picked;
    @attr('number') sequence_number;
    @attr('string') status;
    @attr('date') picked_at;
    @attr('string') picked_by_uuid;
    @attr('string') lot_number;
    @attr('string') serial_number;
    @attr('string') notes;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;
}
