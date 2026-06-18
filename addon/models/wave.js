import Model, { attr, belongsTo, hasMany } from '@ember-data/model';

export default class WaveModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') warehouse_uuid;
    @belongsTo('warehouse', { async: false }) warehouse;
    @hasMany('pick-list', { async: false }) pickLists;
    @attr('string') wave_number;
    @attr('string') type;
    @attr('string') status;
    @attr('number') priority;
    @attr('date') scheduled_at;
    @attr('date') started_at;
    @attr('date') completed_at;
    @attr('number') total_pick_lists;
    @attr('number') completed_pick_lists;
    @attr('string') notes;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;
}
