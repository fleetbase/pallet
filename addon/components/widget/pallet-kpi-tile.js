import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetPalletKpiTileComponent extends Component {
    @service fetch;
    @service intl;
    @service hostRouter;

    @tracked data = null;
    @tracked error = null;

    constructor() {
        super(...arguments);
        this.load.perform();
    }

    get metric() {
        return this.data?.[this.args.metric] ?? {};
    }

    /**
     * Where this tile leads.
     *
     * SCREENS.md §A gives every widget a click-through to a pre-filtered list, because
     * the manager reading this screen "came to find out what needs them today". A tile
     * reading "LOW STOCK 3" that cannot be clicked makes them go and find those three
     * themselves, which is the opposite of the point.
     *
     * Tiles without a sensible destination stay inert rather than linking somewhere
     * approximate — a link that lands on the wrong list is worse than no link.
     */
    get hasRoute() {
        return Boolean(this.args.route);
    }

    @action openRoute() {
        if (!this.args.route) {
            return;
        }

        return this.hostRouter.transitionTo(`console.pallet.${this.args.route}`);
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
