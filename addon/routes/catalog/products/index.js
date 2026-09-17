import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class ProductsIndexRoute extends Route {
    @service store;

    queryParams = {
        page: { refreshModel: true },
        limit: { refreshModel: true },
        sort: { refreshModel: true },
        query: { refreshModel: true },
        sku: { refreshModel: true },
        name: { refreshModel: true },
        createdAt: { refreshModel: true },
        updatedAt: { refreshModel: true },
        internal_id: { refreshModel: true },
        public_id: { refreshModel: true },
        price: { refreshModel: true },
        sale_price: { refreshModel: true },
        declared_value: { refreshModel: true },
        length: { refreshModel: true },
        width: { refreshModel: true },
        height: { refreshModel: true },
        weight: { refreshModel: true },
    };

    model(params) {
        return this.store.query('pallet-product', params);
    }
}
