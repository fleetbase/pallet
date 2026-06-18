import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsPickListsRoute extends Route {
    @service store;

    model() {
        return this.store.query('pick-list', {
            limit: 50,
            sort: '-created_at',
            with: ['warehouse', 'wave', 'assignedTo', 'items.product', 'items.variant', 'items.inventory', 'items.binLocation'],
        });
    }
}
