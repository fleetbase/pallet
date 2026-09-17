import Component from '@glimmer/component';
import { action } from '@ember/object';

export default class WarehouseZoneFormComponent extends Component {
    /**
     * The vocabularies WarehouseZone::boot() documents and normalises to.
     */
    typeOptions = ['general', 'receiving', 'shipping', 'staging', 'returns', 'cold_storage'];

    statusOptions = ['active', 'inactive', 'maintenance'];

    @action setWarehouse(warehouse) {
        this.args.resource.warehouse = warehouse;
        this.args.resource.warehouse_uuid = warehouse?.uuid ?? warehouse?.id;
    }
}
