import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetStockValueComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked warehouses = [];
    @tracked totalValue = 0;

    constructor() {
        super(...arguments);
        this.loadStockValue.perform();
    }

    @task({ restartable: true })
    *loadStockValue() {
        try {
            const data = yield this.fetch.get('metrics/stock-value', {}, { namespace: 'pallet/int/v1' });
            this.warehouses = data.warehouses ?? [];
            this.totalValue = data.total_value ?? 0;
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    get maxValue() {
        if (!this.warehouses.length) return 1;
        return Math.max(...this.warehouses.map((w) => w.value ?? 0));
    }

    barWidth(value) {
        if (!this.maxValue) return '0%';
        return `${Math.round((value / this.maxValue) * 100)}%`;
    }
}
