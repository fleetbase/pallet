import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class SalesOrdersIndexNewController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof SalesOrdersIndexNewController
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof SalesOrdersIndexNewController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof SalesOrdersIndexNewController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof SalesOrdersIndexNewController
     */
    @tracked overlay;

    /**
     * The salesOrder being created.
     *
     * @var {EntityModel}
     */
    @tracked salesOrder = this.store.createRecord('pallet-salesOrder', { type: 'pallet-salesOrder' });

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof SalesOrdersIndexNewController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof SalesOrdersIndexNewController
     */
    @action transitionBack() {
        return this.transitionToRoute('salesOrders.index');
    }

    /**
     * Trigger a route refresh and focus the new salesOrder created.
     *
     * @param {salesOrderModel} salesOrder
     * @return {Promise}
     * @memberof SalesOrdersIndexNewController
     */
    @action onAfterSave(salesOrder) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('salesOrders.index.details', salesOrder).then(() => {
            this.resetForm();
        });
    }

    /**
     * Resets the form with a new salesOrder record
     *
     * @memberof SalesOrdersIndexNewController
     */
    resetForm() {
        this.salesOrder = this.store.createRecord('sales-order');
    }
}
