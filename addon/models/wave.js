import Model, { attr, belongsTo, hasMany } from '@ember-data/model';
import { computed } from '@ember/object';

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

    /**
     * The list rendered one line per pick list, so a wave with six of them was a
     * six-line row. A count reads the same and keeps the row one line.
     */
    @computed('pickLists.[]') get pickListsSummary() {
        const count = (this.pickLists ?? []).length;

        if (count === 0) {
            return null;
        }

        return count === 1 ? '1 pick list' : `${count} pick lists`;
    }

    @computed('completed_pick_lists', 'total_pick_lists') get progress() {
        return `${this.completed_pick_lists ?? 0} / ${this.total_pick_lists ?? 0}`;
    }
}
