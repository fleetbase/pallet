import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetFulfillmentWorkloadComponent extends Component {
    @service fetch;

    @tracked data = null;
    @tracked error = null;

    constructor() {
        super(...arguments);
        this.load.perform();
    }

    get groups() {
        const data = this.data ?? {};
        return [
            { label: 'Reservations', icon: 'lock', counts: data.reservations ?? {} },
            { label: 'Waves', icon: 'water', counts: data.waves ?? {} },
            { label: 'Pick Lists', icon: 'list-check', counts: data.pick_lists ?? {} },
            { label: 'Cycle Counts', icon: 'clipboard-list', counts: data.cycle_counts ?? {} },
            { label: 'Transfers', icon: 'right-left', counts: data.transfers ?? {} },
        ].map((group) => ({
            ...group,
            total: Object.values(group.counts).reduce((sum, value) => sum + Number(value ?? 0), 0),
        }));
    }

    @task *load() {
        try {
            this.data = yield this.fetch.get('metrics/fulfillment-workload', {}, { namespace: 'pallet/int/v1' });
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load fulfillment workload';
        }
    }
}
