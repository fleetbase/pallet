import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetLowStockComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked items = [];
    @tracked error = null;

    constructor() {
        super(...arguments);
        this.loadLowStock.perform();
    }

    @task({ restartable: true })
    *loadLowStock() {
        try {
            const data = yield this.fetch.get('metrics/low-stock', { limit: 10 }, { namespace: 'pallet/int/v1' });
            this.items = data.items ?? [];
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load low stock alerts';
            this.notifications.serverError(error);
        }
    }
}
