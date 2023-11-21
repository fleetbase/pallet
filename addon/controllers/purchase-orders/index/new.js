import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class PurchaseOrdersIndexNewController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof PurchaseOrdersIndexNewController
     */
    @service store;

    /**
     * Inject the `currentUser` service
     *
     * @memberof PurchaseOrdersIndexNewController
     */
    @service currentUser;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof PurchaseOrdersIndexNewController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof PurchaseOrdersIndexNewController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof PurchaseOrdersIndexNewController
     */
    @tracked overlay;

    /**
     * The fuel report being created.
     *
     * @var {FuelReportModel}
     */
    @tracked purchaseOrder = this.store.createRecord('purchase-order');

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof PurchaseOrdersIndexNewController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof PurchaseOrdersIndexNewController
     */
    @action transitionBack() {
        return this.transitionToRoute('purchase-orders.index');
    }

    /**
     * Trigger a route refresh and focus the new fuel report created.
     *
     * @param {PurchaseOrderModel} purchaseOrder
     * @return {Promise}
     * @memberof PurchaseOrdersIndexNewController
     */
    @action onAfterSave(purchaseOrder) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('purchase-orders.index.details', purchaseOrder).then(() => {
            this.resetForm();
        });
    }

    /**
     * Resets the form with a new fuel report record
     *
     * @memberof PurchaseOrdersIndexNewController
     */
    resetForm() {
        this.purchaseOrder = this.store.createRecord('purchase-order');
    }
}
