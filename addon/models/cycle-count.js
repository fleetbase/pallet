import Model, { attr, belongsTo, hasMany } from '@ember-data/model';

export default class CycleCountModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') warehouse_uuid;
    @attr('string') zone_uuid;
    @attr('string') assigned_to_uuid;
    @belongsTo('warehouse', { async: false }) warehouse;
    @belongsTo('warehouse-zone', { async: false }) zone;
    @hasMany('cycle-count-item', { async: false }) items;
    @attr('string') count_number;
    @attr('string') type;
    @attr('string') status;
    @attr('date') scheduled_at;
    @attr('date') started_at;
    @attr('date') completed_at;
    @attr('number') total_items;
    @attr('number') counted_items;
    @attr('number') discrepancies_count;
    @attr('number') accuracy_percentage;
    @attr('string') notes;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;
}
