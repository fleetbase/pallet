import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class PurchaseOrdersIndexEditController extends Controller {
    /**
     * Inject the `hostRouter` service
     *
     * @memberof PurchaseOrdersIndexEditController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof PurchaseOrdersIndexEditController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof PurchaseOrdersIndexEditController
     */
    @tracked overlay;

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof PurchaseOrdersIndexEditController
     */
    @action transitionBack(purchaseOrder) {
        if (purchaseOrder.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(purchaseOrder, {
                confirm: () => {
                    purchaseOrder.rollbackAttributes();
                    return this.transitionToRoute('purchase-orders.index');
                },
            });
        }

        return this.transitionToRoute('purchase-orders.index');
    }

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof PurchaseOrdersIndexEditController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When fuel-report details button is clicked in overlay.
     *
     * @param {PurchaseOrderModel} purchaseOrder
     * @return {Promise}
     * @memberof PurchaseOrdersIndexEditController
     */
    @action onViewDetails(purchaseOrder) {
        // check if fuel-report record has been edited and prompt for confirmation
        if (purchaseOrder.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(purchaseOrder);
        }

        return this.transitionToRoute('purchase-orders.index.details', purchaseOrder);
    }

    /**
     * Trigger a route refresh and focus the new fuel-report created.
     *
     * @param {PurchaseOrderModel} purchaseOrder
     * @return {Promise}
     * @memberof PurchaseOrdersIndexEditController
     */
    @action onAfterSave(purchaseOrder) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('purchase-orders.details', purchaseOrder);
    }

    /**
     * Prompts the user to confirm if they wish to continue with unsaved changes.
     *
     * @method
     * @param {FuelReportModel} purchaseOrdert - The fuel-report object with unsaved changes.
     * @param {Object} [options={}] - Additional options for configuring the modal.
     * @returns {Promise} A promise that resolves when the user confirms, and transitions to a new route.
     * @memberof PurchaseOrdersIndexEditController
     */
    confirmContinueWithUnsavedChanges(purchaseOrder, options = {}) {
        return this.modalsManager.confirm({
            title: 'Continue Without Saving?',
            body: 'Unsaved changes to this purchase-order will be lost. Click continue to proceed.',
            acceptButtonText: 'Continue without saving',
            confirm: () => {
                purchaseOrder.rollbackAttributes();
                return this.transitionToRoute('purchase-orders.index.details', purchaseOrder);
            },
            ...options,
        });
    }
}
