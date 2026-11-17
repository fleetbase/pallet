import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class ZonesIndexRoute extends Route {
    @service store;

    queryParams = {
        page: { refreshModel: true },
        limit: { refreshModel: true },
        sort: { refreshModel: true },
        query: { refreshModel: true },
        status: { refreshModel: true },
        type: { refreshModel: true },
        created_at: { refreshModel: true },
        updated_at: { refreshModel: true },
    };

    model(params) {
        return this.store.query('warehouse-zone', { ...params, with: ['warehouse'] });
    }
}
