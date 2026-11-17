import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetInventorySummaryComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked totalSkus = 0;
    @tracked totalUnits = 0;
    @tracked totalValue = 0;
    @tracked warehouseCount = 0;
    @tracked lowStockCount = 0;

    constructor() {
        super(...arguments);
        this.loadMetrics.perform();
    }

    @task({ restartable: true })
    *loadMetrics() {
        try {
            const data = yield this.fetch.get('metrics/inventory-summary', {}, { namespace: 'pallet/int/v1' });
            this.totalSkus = data.total_skus ?? 0;
            this.totalUnits = data.total_units ?? 0;
            this.totalValue = data.total_value ?? 0;
            this.warehouseCount = data.warehouse_count ?? 0;
            this.lowStockCount = data.low_stock_count ?? 0;
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
