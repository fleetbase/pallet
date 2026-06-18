import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class PurchaseOrderFormComponent extends Component {
    @tracked statusOptions = [
        { label: 'Draft', value: 'draft' },
        { label: 'Pending', value: 'pending' },
        { label: 'Approved', value: 'approved' },
        { label: 'Received', value: 'received' },
        { label: 'Cancelled', value: 'cancelled' },
    ];

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    @action setSupplier(supplier) {
        this.args.resource.supplier = supplier;
        this.args.resource.supplier_uuid = this.getRecordUuid(supplier);
    }

    @action setWarehouse(warehouse) {
        this.args.resource.warehouse = warehouse;
        this.args.resource.warehouse_uuid = this.getRecordUuid(warehouse);
    }
}
