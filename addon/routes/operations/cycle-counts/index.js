import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsCycleCountsRoute extends Route {
    @service store;

    queryParams = {
        page: { refreshModel: true },
        limit: { refreshModel: true },
        sort: { refreshModel: true },
        query: { refreshModel: true },
        status: { refreshModel: true },
    };

    model(params) {
        return this.store.query('cycle-count', {
            ...params,
            with: ['warehouse', 'zone', 'assignedTo', 'items.product', 'items.variant', 'items.inventory', 'items.binLocation'],
        });
    }
}
