import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class PurchaseOrdersIndexDetailsRoute extends Route {
    @service store;

    queryParams = {
        view: { refreshModel: false },
    };

    model({ public_id }) {
        return this.store.findRecord('purchase-order', public_id);
    }
}
