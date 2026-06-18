import Model, { attr, belongsTo, hasMany } from '@ember-data/model';

export default class PickListModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') warehouse_uuid;
    @attr('string') sales_order_uuid;
    @attr('string') wave_uuid;
    @attr('string') assigned_to_uuid;
    @belongsTo('warehouse', { async: false }) warehouse;
    @belongsTo('wave', { async: false }) wave;
    @belongsTo('user') assignedTo;
    @hasMany('pick-list-item', { async: false }) items;
    @attr('string') pick_list_number;
    @attr('string') type;
    @attr('number') priority;
    @attr('string') status;
    @attr('date') started_at;
    @attr('date') completed_at;
    @attr('number') total_items;
    @attr('number') picked_items;
    @attr('number') completion_percentage;
    @attr('string') notes;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;
}
