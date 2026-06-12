import Model, { attr, belongsTo, hasMany } from '@ember-data/model';

export default class WarehouseZoneModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') warehouse_uuid;
    @belongsTo('warehouse', { async: false }) warehouse;
    @hasMany('bin-location', { async: false }) binLocations;
    @attr('string') name;
    @attr('string') code;
    @attr('string') type;
    @attr('string') status;
    @attr('boolean') temperature_controlled;
    @attr() temperature_range;
    @attr('number') capacity;
    @attr('number') current_utilization;
    @attr('number') utilization_percentage;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;
}
