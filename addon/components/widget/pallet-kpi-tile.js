import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetPalletKpiTileComponent extends Component {
    @service fetch;

    @tracked data = null;
    @tracked error = null;

    constructor() {
        super(...arguments);
        this.load.perform();
    }

    get metric() {
        return this.data?.[this.args.metric] ?? {};
    }

    get value() {
        const value = this.metric.value ?? 0;

        if (this.metric.format === 'currency') {
            return new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency: this.metric.currency ?? 'USD',
                maximumFractionDigits: 0,
            }).format(Number(value));
        }

        return Number(value).toLocaleString();
    }

    get footnote() {
        return this.args.footnote ?? this.metric.footnote ?? 'Current';
    }

    get accentClass() {
        return `pallet-kpi-accent-${this.args.accent ?? 'blue'}`;
    }

    @task *load() {
        try {
            this.data = yield this.fetch.get('metrics/kpis', {}, { namespace: 'pallet/int/v1' });
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load KPI';
        }
    }
}
