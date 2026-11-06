import Component from '@glimmer/component';
import { action } from '@ember/object';

export default class BinLocationFormComponent extends Component {
    /**
     * The vocabularies BinLocation::boot() documents and normalises to.
     */
    typeOptions = ['standard', 'bulk', 'pallet', 'shelf'];

    statusOptions = ['active', 'inactive', 'maintenance'];

    @action setWarehouse(warehouse) {
        this.args.resource.warehouse = warehouse;
        this.args.resource.warehouse_uuid = warehouse?.uuid ?? warehouse?.id;
        // a zone belongs to a warehouse, so it cannot survive the warehouse changing
        this.args.resource.zone = null;
        this.args.resource.zone_uuid = null;
    }

    @action setZone(zone) {
        this.args.resource.zone = zone;
        this.args.resource.zone_uuid = zone?.uuid ?? zone?.id;
    }
}
