import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetStockMovementComponent extends Component {
    @service fetch;

    @tracked series = [];
    @tracked error = null;

    constructor() {
        super(...arguments);
        this.load.perform();
    }

    get totals() {
        const totals = {};
        for (const row of this.series) {
            const type = row.type ?? 'movement';
            totals[type] = (totals[type] ?? 0) + Number(row.quantity ?? 0);
        }

        return Object.entries(totals)
            .map(([type, quantity]) => ({ type, label: type.replaceAll('_', ' '), quantity }))
            .sort((a, b) => b.quantity - a.quantity);
    }

    @task *load() {
        try {
            const data = yield this.fetch.get('metrics/stock-movement', { days: 14 }, { namespace: 'pallet/int/v1' });
            this.series = data.series ?? [];
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load stock movement';
        }
    }
}
