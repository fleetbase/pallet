import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetStockMovementComponent extends Component {
    @service fetch;

    @tracked series = [];
    @tracked totalRows = [];
    @tracked dailyRows = [];
    @tracked totalQuantity = 0;
    @tracked error = null;

    constructor() {
        super(...arguments);
        this.load.perform();
    }

    get totals() {
        if (this.totalRows.length) {
            return this.totalRows;
        }

        const totals = {};
        for (const row of this.series) {
            const type = row.type ?? 'movement';
            totals[type] = (totals[type] ?? 0) + Number(row.quantity ?? 0);
        }

        return Object.entries(totals)
            .map(([type, quantity]) => ({ type, label: type.replaceAll('_', ' '), quantity }))
            .sort((a, b) => b.quantity - a.quantity);
    }

    get maxDailyQuantity() {
        return Math.max(...this.dailyRows.map((row) => Number(row.quantity ?? 0)), 1);
    }

    get dailyBars() {
        return this.dailyRows.map((row) => {
            const quantity = Number(row.quantity ?? 0);
            const height = Math.max(10, Math.round((quantity / this.maxDailyQuantity) * 100));

            return {
                ...row,
                quantity,
                heightStyle: `height: ${height}%;`,
            };
        });
    }

    @task *load() {
        try {
            const data = yield this.fetch.get('metrics/stock-movement', { days: 14 }, { namespace: 'pallet/int/v1' });
            this.series = data.series ?? [];
            this.totalRows = data.totals ?? [];
            this.dailyRows = data.daily ?? [];
            this.totalQuantity = data.total_quantity ?? 0;
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load stock movement';
        }
    }
}
