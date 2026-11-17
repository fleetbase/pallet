import ReportActionsBaseService from '@fleetbase/ember-core/services/report-actions';

export default class ReportActionsService extends ReportActionsBaseService {
    defaultAttributes = { type: 'pallet' };

    /**
     * ResourceActionService defaults mountPrefix to fleet-ops and only overrides it
     * inside initialize(), which the base ReportActionsService calls without one.
     * Every transition from Pallet's Reports screen therefore navigated into
     * Fleet-Ops: clicking New swapped the whole console shell over to another
     * engine. A subclass field is assigned after super() runs, which is how
     * defaultAttributes above already takes effect.
     */
    mountPrefix = 'console.pallet';

    transition = {
        view: (report) => this.transitionTo('analytics.reports.index.details', report),
        edit: (report) => this.transitionTo('analytics.reports.index.edit', report),
        create: () => this.transitionTo('analytics.reports.index.new'),
    };
}
