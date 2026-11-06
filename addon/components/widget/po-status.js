import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetPoStatusComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked pending = 0;
    @tracked partiallyReceived = 0;
    @tracked received = 0;
    @tracked cancelled = 0;
    @tracked recentOrders = [];
    @tracked error = null;

    get total() {
        return this.pending + this.partiallyReceived + this.received + this.cancelled;
    }

    constructor() {
        super(...arguments);
        this.loadStatus.perform();
    }

    @task({ restartable: true })
    *loadStatus() {
        try {
            const data = yield this.fetch.get('metrics/po-status', {}, { namespace: 'pallet/int/v1' });
            this.pending = data.pending ?? 0;
            this.partiallyReceived = data.partially_received ?? 0;
            this.received = data.received ?? 0;
            this.cancelled = data.cancelled ?? 0;
            this.recentOrders = data.recent ?? [];
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load purchase order status';
            this.notifications.serverError(error);
        }
    }
}
