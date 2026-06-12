import Model, { attr, belongsTo } from '@ember-data/model';

export default class BinLocationModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') warehouse_uuid;
    @attr('string') zone_uuid;
    @attr('string') aisle_uuid;
    @attr('string') rack_uuid;
    @attr('string') section_uuid;
    @belongsTo('warehouse', { async: false }) warehouse;
    @belongsTo('warehouse-zone', { async: false }) zone;
    @attr('string') bin_number;
    @attr('string') barcode;
    @attr('string') type;
    @attr('string') status;
    @attr('number') capacity;
    @attr('number') current_volume;
    @attr('number') available_capacity;
    @attr('number') utilization_percentage;
    @attr() dimensions;
    @attr('boolean') is_pickable;
    @attr('boolean') is_replenishable;
    @attr('number') priority;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;
}
