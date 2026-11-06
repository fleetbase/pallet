import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';

export default class SupplierFormComponent extends Component {
    @tracked typeOptions = [
        { label: 'Manufacturer', value: 'manufacturer' },
        { label: 'Distributor', value: 'distributor' },
        { label: 'Wholesaler', value: 'wholesaler' },
    ];
    @tracked statusOptions = [
        { label: 'Active', value: 'active' },
        { label: 'Inactive', value: 'inactive' },
    ];
}
