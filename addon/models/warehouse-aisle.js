import Model, { attr, belongsTo, hasMany } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate } from 'date-fns';

export default class WarehouseAisle extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') section_uuid;

    /** @relationships */
    @belongsTo('company') company;
    @belongsTo('user') createdBy;
    @hasMany('warehouse-rack') racks;

    /** @attributes */
    @attr('string') aisle_number;
    @attr('polygon') area;
    @attr('raw') meta;

    /** @date */
    @attr('date') created_at;
    @attr('date') updated_at;

    /** @computed */
    @computed('updated_at') get updatedAgo() {
        if (!isValidDate(this.updated_at)) {
            return null;
        }
        return formatDistanceToNow(this.updated_at);
    }

    @computed('updated_at') get updatedAt() {
        if (!isValidDate(this.updated_at)) {
            return null;
        }
        return formatDate(this.updated_at, 'PPP p');
    }

    @computed('area.coordinates', 'isNew') get locations() {
        let coordinates = getWithDefault(this.area, 'coordinates', []);

        // hotfix patch when coordinates are wrapped in array
        if (isArray(coordinates) && isArray(coordinates[0]) && coordinates[0].length > 2) {
            coordinates = first(coordinates);
        }

        if (this.isNew) {
            return coordinates;
        }

        return coordinates.map((coord) => coord.reverse());
    }

    @computed('bounds') get firstCoordinatePair() {
        return first(this.bounds) ?? [0, 0];
    }

    @computed('locations') get centerCoordinates() {
        const x = this.locations.map((xy) => xy[0]);
        const y = this.locations.map((xy) => xy[1]);
        const cx = (Math.min(...x) + Math.max(...x)) / 2;
        const cy = (Math.min(...y) + Math.max(...y)) / 2;

        return [cx, cy];
    }
}
