import Model, { attr, belongsTo } from '@ember-data/model';

export default class CycleCountItemModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') cycle_count_uuid;
    @attr('string') product_uuid;
    @attr('string') variant_uuid;
    @attr('string') inventory_uuid;
    @attr('string') bin_location_uuid;
    @belongsTo('pallet-product', { async: false }) product;
    @belongsTo('pallet-product-variant', { async: false }) variant;
    @belongsTo('inventory', { async: false }) inventory;
    @belongsTo('bin-location', { async: false }) binLocation;
    @attr('number') expected_quantity;
    @attr('number') counted_quantity;
    @attr('number') variance;
    @attr('boolean') has_discrepancy;
    @attr('string') status;
    @attr('date') counted_at;
    @attr('string') counted_by_uuid;
    @attr('string') lot_number;
    @attr('string') serial_number;
    @attr('string') notes;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;
}
