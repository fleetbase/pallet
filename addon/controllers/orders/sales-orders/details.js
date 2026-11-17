import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class SalesOrdersDetailsController extends Controller {
    @service hostRouter;
    @service intl;

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
            route: 'orders.sales-orders.details.index',
            label: 'Overview',
        },
    ];

    /**
     * Buttons for the document header.
     *
     * Rendered by the template into Layout::Section::Header's block, because that
     * component has no @actionButtons argument — its only yield is the actions
     * wormhole, so a button passed as an argument would render nowhere.
     */
    get actionButtons() {
        return [
            {
                icon: 'pencil',
                text: this.intl.t('common.edit'),
                fn: () => this.onEdit(this.model),
            },
        ];
    }

    /**
     * Transitions back to the "sales-order.index" route.
     *
     * @method
     * @action
     * @returns {Transition} The transition object representing the route change.
     */
    @action transitionBack() {
        return this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index');
    }

    /**
     * Transitions to the edit view for a specific vehicle.
     *
     * @param {SalesOrderModel} salesOrder
     * @method
     * @action
     * @returns {Transition} The transition object representing the route change.
     */
    @action onEdit(salesOrder) {
        return this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index.edit', salesOrder);
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
