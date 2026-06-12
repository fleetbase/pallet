import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class InventoryAdjustmentsRoute extends Route {
    @service store;

    model() {
        return this.store.query('stock-adjustment', { limit: 50, sort: '-created_at', with: ['product', 'variant'] });
    }
}
