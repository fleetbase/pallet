import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsCycleCountsCountRoute extends Route {
    @service store;

    model({ public_id }) {
        return this.store.findRecord('cycle-count', public_id, {
            include: ['warehouse', 'zone', 'items.product', 'items.binLocation'].join(','),
        });
    }
}
