import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetSoStatusComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked pending = 0;
    @tracked partiallyFulfilled = 0;
    @tracked fulfilled = 0;
    @tracked cancelled = 0;
    @tracked recentOrders = [];

    get total() {
        return this.pending + this.partiallyFulfilled + this.fulfilled + this.cancelled;
    }

    constructor() {
        super(...arguments);
        this.loadStatus.perform();
    }

    @task({ restartable: true })
    *loadStatus() {
        try {
            const data = yield this.fetch.get('metrics/so-status', {}, { namespace: 'pallet/int/v1' });
            this.pending = data.pending ?? 0;
            this.partiallyFulfilled = data.partially_fulfilled ?? 0;
            this.fulfilled = data.fulfilled ?? 0;
            this.cancelled = data.cancelled ?? 0;
            this.recentOrders = data.recent ?? [];
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
