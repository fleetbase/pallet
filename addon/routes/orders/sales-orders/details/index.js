import Route from '@ember/routing/route';

export default class OrdersSalesOrdersIndexDetailsIndexRoute extends Route {
    /**
     * The record is loaded by the parent details route; without this the
     * overview tab renders with no model.
     */
    model() {
        return this.modelFor('orders.sales-orders.details');
    }
}
