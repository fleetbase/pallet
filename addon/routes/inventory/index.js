import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class InventoryIndexRoute extends Route {
    @service store;

    queryParams = {
        page: { refreshModel: true },
        limit: { refreshModel: true },
        sort: { refreshModel: true },
        query: { refreshModel: true },
        product: { refreshModel: true },
        warehouse: { refreshModel: true },
        batch: { refreshModel: true },
    };

    model(params) {
        return this.store.query('inventory', { ...params, with: ['product'] });
    }
}
