import Route from '@ember/routing/route';

export default class OrdersPurchaseOrdersIndexDetailsIndexRoute extends Route {
    /**
     * The record is loaded by the parent details route; without this the
     * overview tab renders with no model.
     */
    model() {
        return this.modelFor('orders.purchase-orders.index.details');
    }
}
