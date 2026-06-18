import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class SalesOrderFormComponent extends Component {
    @tracked statusOptions = [
        { label: 'Draft', value: 'draft' },
        { label: 'Pending', value: 'pending' },
        { label: 'Processing', value: 'processing' },
        { label: 'Shipped', value: 'shipped' },
        { label: 'Delivered', value: 'delivered' },
        { label: 'Cancelled', value: 'cancelled' },
    ];

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    @action setCustomer(customer) {
        this.args.resource.customer = customer;
        this.args.resource.customer_uuid = this.getRecordUuid(customer);
    }

    @action setWarehouse(warehouse) {
        this.args.resource.warehouse = warehouse;
        this.args.resource.warehouse_uuid = this.getRecordUuid(warehouse);
    }
}
