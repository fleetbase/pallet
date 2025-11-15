import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class InventoryFormComponent extends Component {
    @tracked statusOptions = [
        { label: 'In Stock', value: 'in_stock' },
        { label: 'Out of Stock', value: 'out_of_stock' },
        { label: 'On Order', value: 'on_order' },
        { label: 'Reserved', value: 'reserved' },
    ];

    @action setExpiryDate(event) {
        if (this.args.resource.batch) {
            this.args.resource.batch.expiryDate = event.target.value;
        }
    }
}
