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
    /**
     * Cancel left the record createRecord() had already put in the store behind on
     * every abandoned create — a fresh orphan each time the panel is reopened, for the
     * life of the session. Same defect the create panels had; this route reaches the
     * exit through transitionBack rather than an onPressCancel action, which is why it
     * was missed the first time round.
     */
    @action transitionBack() {
        const record = this.salesOrder;

        if (record?.isNew) {
            record.rollbackAttributes();
        }

        return this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index');
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
        return this.hostRouter.transitionTo('console.pallet.orders.sales-orders.details', salesOrder).then(() => {
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
