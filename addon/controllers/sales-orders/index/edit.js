import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class SalesOrdersIndexEditController extends Controller {
    /**
     * Inject the `hostRouter` service
     *
     * @memberof SalesOrdersIndexEditController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof SalesOrdersIndexEditController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof SalesOrdersIndexEditController
     */
    @tracked overlay;

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof salesOrdersIndexEditController
     */
    @action transitionBack(salesOrder) {
        // check if salesOrder record has been edited and prompt for confirmation
        if (salesOrder.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(salesOrder, {
                confirm: () => {
                    salesOrder.rollbackAttributes();
                    return this.transitionToRoute('salesOrders.index');
                },
            });
        }

        return this.transitionToRoute('salesOrders.index');
    }

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof SalesOrdersIndexEditController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When salesOrder details button is clicked in overlay.
     *
     * @param {SalesOrderModel} salesOrder
     * @return {Promise}
     * @memberof SalesOrdersIndexEditController
     */
    @action onViewDetails(salesOrder) {
        // check if salesOrder record has been edited and prompt for confirmation
        if (salesOrder.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(salesOrder);
        }

        return this.transitionToRoute('salesOrders.index.details', salesOrder);
    }

    /**
     * Trigger a route refresh and focus the new salesOrder created.
     *
     * @param {SalesOrderModel} salesOrder
     * @return {Promise}
     * @memberof SalesOrdersIndexEditController
     */
    @action onAfterSave(salesOrder) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('salesOrders.index.details', salesOrder);
    }

    /**
     * Prompts the user to confirm if they wish to continue with unsaved changes.
     *
     * @method
     * @param {SalesOrderModel} salesOrder - The salesOrder object with unsaved changes.
     * @param {Object} [options={}] - Additional options for configuring the modal.
     * @returns {Promise} A promise that resolves when the user confirms, and transitions to a new route.
     * @memberof SalesOrdersIndexEditController
     */
    confirmContinueWithUnsavedChanges(salesOrder, options = {}) {
        return this.modalsManager.confirm({
            title: 'Continue Without Saving?',
            body: 'Unsaved changes to this salesOrder will be lost. Click continue to proceed.',
            acceptButtonText: 'Continue without saving',
            confirm: () => {
                salesOrder.rollbackAttributes();
                return this.transitionToRoute('salesOrders.index.details', salesOrder);
            },
            ...options,
        });
    }
}
