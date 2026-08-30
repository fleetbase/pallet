import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetPalletKpiTileComponent extends Component {
    @service fetch;
    @service intl;

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
        const raw = this.metric.value;

        // A null value means the metric could not be computed, which is not the same as
        // a value of zero — the stock-value tile reported "$0" against 94 units on hand
        // because no inventory row carried a unit cost. Zero still renders as zero; only
        // an absent value falls back to a dash, and the footnote says why.
        if (raw === null || raw === undefined) {
            return '–';
        }

        const value = raw;

        // `undefined` here means the *browser's* locale, not the console's, so the
        // stock-value tile rendered "0 US$" on a machine set to another locale
        // while every other currency in the module read "$0.00". Numbers had the
        // same fault through toLocaleString.
        const locale = this.intl.primaryLocale ?? 'en-us';

        if (this.metric.format === 'currency') {
            return new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: this.metric.currency ?? 'USD',
                maximumFractionDigits: 0,
            }).format(Number(value));
        }

        return new Intl.NumberFormat(locale).format(Number(value));
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
