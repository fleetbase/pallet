import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetLowStockComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked items = [];

    constructor() {
        super(...arguments);
        this.loadLowStock.perform();
    }

    @task({ restartable: true })
    *loadLowStock() {
        try {
            const data = yield this.fetch.get('metrics/low-stock', { limit: 10 }, { namespace: 'pallet/int/v1' });
            this.items = data.items ?? [];
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
