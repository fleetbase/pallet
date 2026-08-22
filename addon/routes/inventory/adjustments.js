import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class InventoryAdjustmentsRoute extends Route {
    @service store;

    queryParams = {
        page: { refreshModel: true },
        limit: { refreshModel: true },
        sort: { refreshModel: true },
        query: { refreshModel: true },
    };

    model(params) {
        return this.store.query('stock-adjustment', {
            ...params,
            with: ['product', 'variant', 'inventory', 'warehouse'],
        });
    }
}
