import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetStockValueComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked warehouses = [];
    @tracked totalValue = 0;
    @tracked error = null;

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
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load stock value';
            this.notifications.serverError(error);
        }
    }

    get maxValue() {
        if (!this.warehouses.length) return 1;
        return Math.max(...this.warehouses.map((w) => w.value ?? 0));
    }

    get warehouseBars() {
        return this.warehouses.map((warehouse) => {
            const filledCount = this.segmentCount(warehouse.value ?? 0);

            return {
                ...warehouse,
                filledSegments: Array.from({ length: filledCount }),
                emptySegments: Array.from({ length: 10 - filledCount }),
            };
        });
    }

    segmentCount(value) {
        if (!this.maxValue) return 0;

        return Math.max(0, Math.min(10, Math.round((value / this.maxValue) * 10)));
    }
}
