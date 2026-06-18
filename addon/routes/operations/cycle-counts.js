import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsCycleCountsRoute extends Route {
    @service store;

    model() {
        return this.store.query('cycle-count', {
            limit: 50,
            sort: '-created_at',
            with: ['warehouse', 'zone', 'assignedTo', 'items.product', 'items.variant', 'items.inventory', 'items.binLocation'],
        });
    }
}
