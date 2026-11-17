import Model, { attr } from '@ember-data/model';

// Stand-in for the console host's `schedule` model, which pallet's models relate to.
export default class ScheduleModel extends Model {
    @attr('string') public_id;
}
