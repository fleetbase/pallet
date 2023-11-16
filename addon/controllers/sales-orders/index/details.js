import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class SalesOrdersIndexDetailsController extends Controller {
    /**
     * The currently active view tab ('details' by default).
     *
     * @type {String}
     * @tracked
     */
    @tracked view = 'details';

    /**
     * An array of query parameters to be serialized in the URL.
     *
     * @type {String[]}
     * @tracked
     */
    @tracked queryParams = ['view'];

    /**
     * Transitions back to the "salesOrders.index" route.
     *
     * @method
     * @action
     * @returns {Transition} The transition object representing the route change.
     */
    @action transitionBack() {
        return this.transitionToRoute('salesOrders.index');
    }

    /**
     * Transitions to the edit view for a specific salesOrder.
     *
     * @method
     * @param {salesOrderModel} salesOrder - The salesOrder to be edited.
     * @action
     * @returns {Transition} The transition object representing the route change.
     */
    @action onEdit(salesOrder) {
        return this.transitionToRoute('salesOrders.index.edit', salesOrder);
    }

    /**
     * Updates the active view tab.
     *
     * @method
     * @param {String} tab - The name of the tab to activate.
     * @action
     */
    @action onTabChanged(tab) {
        this.view = tab;
    }
}
