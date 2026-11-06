import Route from '@ember/routing/route';

export default class AnalyticsReportsIndexDetailsResultRoute extends Route {
    /**
     * The report is loaded by the parent details route; without this the
     * template's `@model` is undefined and the result table renders empty.
     */
    model() {
        return this.modelFor('analytics.reports.index.details');
    }
}
