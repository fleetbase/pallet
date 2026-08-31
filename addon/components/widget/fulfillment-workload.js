import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetFulfillmentWorkloadComponent extends Component {
    @service intl;
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
            { label: this.intl.t('widgets.labels.reservations'), icon: 'lock', counts: data.reservations ?? {} },
            { label: this.intl.t('widgets.labels.waves'), icon: 'water', counts: data.waves ?? {} },
            { label: this.intl.t('widgets.labels.pick-lists'), icon: 'list-check', counts: data.pick_lists ?? {} },
            { label: this.intl.t('widgets.labels.cycle-counts'), icon: 'clipboard-list', counts: data.cycle_counts ?? {} },
            { label: this.intl.t('widgets.labels.transfers'), icon: 'right-left', counts: data.transfers ?? {} },
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
