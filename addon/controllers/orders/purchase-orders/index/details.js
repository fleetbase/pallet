import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class PurchaseOrdersIndexDetailsController extends Controller {
    @service hostRouter;

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
     * The panel renders a TabNavigation over these; without them the details
     * body had no tabs and no outlet content at all.
     */
    @tracked tabs = [
        {
            route: 'orders.purchase-orders.index.details.index',
            label: 'Overview',
        },
    ];

    /**
     * The panel header's action buttons. The template binds this; without it the
     * details panel renders no way to reach the record's edit form.
     */
    get actionButtons() {
        return [
            {
                icon: 'pencil',
                fn: () => this.onEdit(this.model),
            },
        ];
    }

    /**
     * Transitions back to the "purchase-order.index" route.
     *
     * @method
     * @action
     * @returns {Transition} The transition object representing the route change.
     */
    @action transitionBack() {
        return this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index');
    }

    /**
     * Transitions to the edit view for a specific vehicle.
     *
     * @param {PurchaseOrderModel} purchaseOrder
     * @method
     * @action
     * @returns {Transition} The transition object representing the route change.
     */
    @action onEdit(purchaseOrder) {
        return this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index.edit', purchaseOrder);
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
