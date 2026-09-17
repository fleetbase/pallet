import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class AnalyticsReportsIndexDetailsRoute extends Route {
    @service store;
    @service notifications;
    @service hostRouter;

    @action error(error) {
        this.notifications.serverError(error);
        if (typeof error.message === 'string' && error.message.endsWith('not found')) {
            return this.hostRouter.transitionTo('console.pallet.analytics.reports.index');
        }
    }

    queryParams = {
        view: { refreshModel: false },
    };

    model({ public_id }) {
        return this.store.queryRecord('report', { public_id, type: 'pallet', single: true });
    }
}
