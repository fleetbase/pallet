import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetWarehouseUtilizationComponent extends Component {
    @service fetch;

    @tracked warehouses = [];
    @tracked error = null;

    constructor() {
        super(...arguments);
        this.load.perform();
    }

    get maxUnits() {
        return Math.max(...this.warehouses.map((warehouse) => warehouse.units ?? 0), 1);
    }

    get rows() {
        return this.warehouses.map((warehouse) => {
            const filled = Math.max(0, Math.min(10, Math.round(((warehouse.units ?? 0) / this.maxUnits) * 10)));
            return {
                ...warehouse,
                filledSegments: Array.from({ length: filled }),
                emptySegments: Array.from({ length: 10 - filled }),
            };
        });
    }

    @task *load() {
        try {
            const data = yield this.fetch.get('metrics/warehouse-utilization', { limit: 8 }, { namespace: 'pallet/int/v1' });
            this.warehouses = data.warehouses ?? [];
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load warehouse utilization';
        }
    }
}
