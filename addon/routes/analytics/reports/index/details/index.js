import Route from '@ember/routing/route';

export default class AnalyticsReportsIndexDetailsIndexRoute extends Route {
    /**
     * The report is loaded by the parent details route; without this the
     * template's `@model` is undefined and the report renders empty.
     */
    model() {
        return this.modelFor('analytics.reports.index.details');
    }
}
