import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsCycleCountsDetailsRoute extends Route {
    @service store;

    queryParams = { view: { refreshModel: false } };

    model({ public_id }) {
        return this.store.findRecord('cycle-count', public_id, {
            include: ['warehouse', 'zone', 'assignedTo', 'items.product', 'items.binLocation'].join(','),
        });
    }
}
