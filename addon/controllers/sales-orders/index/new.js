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
     * Inject the `currentUser` service
     *
     * @memberof SalesOrdersIndexNewController
     */
    @service currentUser;

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
     * The fuel report being created.
     *
     * @var {SalesOrderModel}
     */
    @tracked salesOrder = this.store.createRecord('sales-order');

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
        return this.transitionToRoute('sales-orders.index');
    }

    /**
     * Trigger a route refresh and focus the new fuel report created.
     *
     * @param {SalesOrderModel} salesOrder
     * @return {Promise}
     * @memberof SalesOrdersIndexNewController
     */
    @action onAfterSave(salesOrder) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('sales-orders.index.details', salesOrder).then(() => {
            this.resetForm();
        });
    }

    /**
     * Resets the form with a new fuel report record
     *
     * @memberof SalesOrdersIndexNewController
     */
    resetForm() {
        this.salesOrder = this.store.createRecord('sales-order');
    }
}
