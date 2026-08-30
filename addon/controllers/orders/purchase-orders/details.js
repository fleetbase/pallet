import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class PurchaseOrdersDetailsController extends Controller {
    @service hostRouter;
    @service intl;
    @service contextPanel;
    @service loader;

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
            route: 'orders.purchase-orders.details.index',
            label: 'Overview',
        },
    ];

    /**
     * Buttons for the document header.
     *
     * Rendered by the template into Layout::Section::Header's block, because that
     * component has no @actionButtons argument — its only yield is the actions wormhole.
     */
    get actionButtons() {
        const buttons = [];

        // SCREENS.md section E: Receive is the primary action on this document, and it
        // is absent rather than disabled once there is nothing left to receive. Until
        // now it existed only in the list's row dropdown, so a clerk reading the order
        // had to go back to the list to act on it.
        if (this.canReceive) {
            buttons.push({
                icon: 'truck-ramp-box',
                type: 'primary',
                text: this.intl.t('purchase-order.actions.receive'),
                fn: () => this.onReceive(this.model),
            });
        }

        buttons.push({
            icon: 'pencil',
            text: this.intl.t('common.edit'),
            fn: () => this.onEdit(this.model),
        });

        return buttons;
    }

    /**
     * Whether this order still has goods to take in.
     *
     * A cancelled order is closed, and an order with nothing outstanding has nothing to
     * receive — offering the action in either case invites a clerk to open a form that
     * can only tell them there is nothing to do. An order with no line items is also
     * excluded: the receive panel's own empty state is the wrong place to learn that
     * the order was never filled in.
     */
    get canReceive() {
        const order = this.model;

        if (!order || order.status === 'cancelled') {
            return false;
        }

        const items = order.items ?? [];

        return items.length > 0 && items.some((item) => (Number(item.quantity) || 0) > (Number(item.quantity_received) || 0));
    }

    /**
     * Opens the receiving panel, reusing the one the list already opens so the two
     * entry points cannot drift apart.
     */
    @action async onReceive(purchaseOrder) {
        this.loader.showOnInitialTransition = false;

        // The document route loads the order, but a reload here keeps the panel's own
        // assumption — that items are present — true regardless of how it was reached.
        if (!purchaseOrder.items || purchaseOrder.items.length === 0) {
            try {
                await purchaseOrder.reload();
            } catch (error) {
                // Proceed with whatever is loaded; the panel renders its own empty state.
            }
        }

        this.contextPanel.focus(purchaseOrder, 'receiving', {
            args: {
                onReceived: () => {
                    this.hostRouter.refresh();
                },
            },
        });
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
