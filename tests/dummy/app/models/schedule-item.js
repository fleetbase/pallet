import Model, { attr } from '@ember-data/model';

// Stand-in for the console host's `schedule-item` model, which pallet's models relate to.
export default class ScheduleItemModel extends Model {
    @attr('string') public_id;
}
