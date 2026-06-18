import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class InventoryBatchesRoute extends Route {
    @service store;

    model() {
        return this.store.query('batch', { limit: 50, sort: '-created_at', with: ['product', 'variant'] });
    }
}
