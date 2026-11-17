import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class PurchaseOrdersIndexEditRoute extends Route {
    @service store;

    model({ public_id }) {
        return this.store.findRecord('purchase-order', public_id);
    }
}
