import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';

export default class SalesOrderFormComponent extends Component {
    @tracked statusOptions = [
        { label: 'Draft', value: 'draft' },
        { label: 'Pending', value: 'pending' },
        { label: 'Processing', value: 'processing' },
        { label: 'Shipped', value: 'shipped' },
        { label: 'Delivered', value: 'delivered' },
        { label: 'Cancelled', value: 'cancelled' },
    ];
}
