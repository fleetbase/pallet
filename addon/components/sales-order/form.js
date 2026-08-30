import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import getOrderStatusOptions from '@fleetbase/pallet-engine/utils/get-order-status-options';

export default class SalesOrderFormComponent extends Component {
    // The statuses the server actually writes — see the util for what each of the
    // four order forms was offering instead.
    @tracked statusOptions = getOrderStatusOptions('sales');

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
