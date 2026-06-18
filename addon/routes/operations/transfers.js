import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsTransfersRoute extends Route {
    @service store;

    model() {
        return this.store.query('stock-transfer', { limit: 50, sort: '-created_at', with: ['fromWarehouse', 'toWarehouse', 'items.product', 'items.variant'] });
    }
}
