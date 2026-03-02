import Model, { attr, belongsTo, hasMany } from '@ember-data/model';
import { computed } from '@ember/object';

export default class WarehouseModel extends Model {
    /** @ids */
    @attr('string') public_id;
    @attr('string') uuid;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') place_uuid;
    @attr('string') manager_uuid;

    /** @relationships */
    @belongsTo('place', { async: false }) place;

    @hasMany('warehouse-section', { async: false }) sections;
    @hasMany('warehouse-dock', { async: false }) docks;
    @hasMany('warehouse-zone', { async: false }) zones;

    /** @attributes */
    @attr('string') name;
    @attr('string') code;
    @attr('string') type;
    @attr('string') status;
    @attr('number') capacity;
    @attr('number') current_utilization;
    @attr('number') utilization_percentage;
    @attr('number') floor_area_sqm;
    @attr('string') timezone;
    @attr('string') phone;
    @attr('string') email;
    @attr('number') total_docks;
    @attr('boolean') is_active;
    @attr('boolean') is_default;
    @attr() operating_hours;
    @attr() meta;

    /** @address fields (proxied from place) */
    @attr('string') address;
    @attr('string') address_html;
    @attr('string') street1;
    @attr('string') street2;
    @attr('string') city;
    @attr('string') province;
    @attr('string') postal_code;
    @attr('string') neighborhood;
    @attr('string') district;
    @attr('string') building;
    @attr('string') country;
    @attr('string') country_name;
    @attr('number') latitude;
    @attr('number') longitude;
    @attr() location;

    /** @computed */
    @attr('number') total_zones;
    @attr('number') total_bins;

    /** @dates */
    @attr('date') updated_at;
    @attr('date') created_at;

    @computed('name', 'code')
    get displayName() {
        if (this.code) {
            return `${this.name} (${this.code})`;
        }
        return this.name;
    }

    @computed('capacity', 'current_utilization')
    get utilizationPercentage() {
        if (!this.capacity || this.capacity === 0) {
            return 0;
        }
        return Math.round((this.current_utilization / this.capacity) * 100 * 100) / 100;
    }
}
