import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsReservationsRoute extends Route {
    @service store;

    model() {
        return this.store.query('inventory-reservation', { limit: 50, sort: '-created_at', with: ['product', 'variant', 'inventory', 'warehouse'] });
    }
}
