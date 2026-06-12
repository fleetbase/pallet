import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetInventoryHealthComponent extends Component {
    @service fetch;

    @tracked data = null;
    @tracked error = null;

    constructor() {
        super(...arguments);
        this.load.perform();
    }

    get items() {
        const data = this.data ?? {};
        return [
            { label: 'In Stock', value: data.in_stock ?? 0, status: 'success' },
            { label: 'Low Stock', value: data.low_stock ?? 0, status: 'warning' },
            { label: 'Out of Stock', value: data.out_of_stock ?? 0, status: 'danger' },
            { label: 'Expired', value: data.expired ?? 0, status: 'danger' },
            { label: 'Expiring Soon', value: data.expiring_soon ?? 0, status: 'warning' },
        ];
    }

    get total() {
        return this.data?.total ?? 0;
    }

    @task *load() {
        try {
            this.data = yield this.fetch.get('metrics/inventory-health', {}, { namespace: 'pallet/int/v1' });
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load inventory health';
        }
    }
}
