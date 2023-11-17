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
     * @memberof SalesOrdersIndexEditController
     */
    @action transitionBack(salesOrder) {
        if (salesOrder.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(salesOrder, {
                confirm: () => {
                    salesOrder.rollbackAttributes();
                    return this.transitionToRoute('sales-orders.index');
                },
            });
        }

        return this.transitionToRoute('sales-orders.index');
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
     * When fuel-report details button is clicked in overlay.
     *
     * @param {SalesOrderModel} salesOrder
     * @return {Promise}
     * @memberof SalesOrdersIndexEditController
     */
    @action onViewDetails(salesOrder) {
        // check if fuel-report record has been edited and prompt for confirmation
        if (salesOrder.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(salesOrder);
        }

        return this.transitionToRoute('sales-orders.index.details', salesOrder);
    }

    /**
     * Trigger a route refresh and focus the new fuel-report created.
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
        return this.transitionToRoute('sales-orders.details', salesOrder);
    }

    /**
     * Prompts the user to confirm if they wish to continue with unsaved changes.
     *
     * @method
     * @param {FuelReportModel} salesOrdert - The fuel-report object with unsaved changes.
     * @param {Object} [options={}] - Additional options for configuring the modal.
     * @returns {Promise} A promise that resolves when the user confirms, and transitions to a new route.
     * @memberof SalesOrdersIndexEditController
     */
    confirmContinueWithUnsavedChanges(salesOrder, options = {}) {
        return this.modalsManager.confirm({
            title: 'Continue Without Saving?',
            body: 'Unsaved changes to this sales-order will be lost. Click continue to proceed.',
            acceptButtonText: 'Continue without saving',
            confirm: () => {
                salesOrder.rollbackAttributes();
                return this.transitionToRoute('sales-orders.index.details', salesOrder);
            },
            ...options,
        });
    }
}
