import Model, { attr, belongsTo } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate } from 'date-fns';

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

    /**
     * The list bound Created At straight to the `date` attribute, so the cell rendered a
     * JavaScript Date through toString — the same fault the zones list had, and the same
     * cause of its table running off the edge. Formatted as every other model here does.
     */
    @computed('created_at') get createdAt() {
        if (!isValidDate(this.created_at)) {
            return null;
        }

        return formatDate(this.created_at, 'PP HH:mm');
    }
}
