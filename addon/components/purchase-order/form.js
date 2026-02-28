import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';

export default class PurchaseOrderFormComponent extends Component {
    @tracked statusOptions = [
        { label: 'Draft', value: 'draft' },
        { label: 'Pending', value: 'pending' },
        { label: 'Approved', value: 'approved' },
        { label: 'Received', value: 'received' },
        { label: 'Cancelled', value: 'cancelled' },
    ];
}
