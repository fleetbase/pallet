import Model, { attr, belongsTo, hasMany } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate } from 'date-fns';

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

    /**
     * The list bound its Created At column straight to the `date` attribute, so the cell
     * rendered a JavaScript Date via toString — "Sat Aug 22 2026 06:20:00 GMT+0800
     * (Singapore Standard Time)" — which took 456px of a 1033px table on its own and
     * pushed everything after it off the screen. Formatted the way every other model in
     * the module formats its dates.
     */
    @computed('created_at') get createdAt() {
        if (!isValidDate(this.created_at)) {
            return null;
        }

        return formatDate(this.created_at, 'PP HH:mm');
    }
}
