import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetLowStockComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked items = [];
    @tracked isLoading = true;

    constructor() {
        super(...arguments);
        this.loadLowStock.perform();
    }

    @task({ restartable: true })
    *loadLowStock() {
        this.isLoading = true;
        try {
            const data = yield this.fetch.get('pallet/metrics/low-stock', { limit: 10 });
            this.items = data.items ?? [];
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.isLoading = false;
        }
    }
}
