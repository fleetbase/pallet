import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class InventoryIndexNewStockAdjustmentController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof InventoryIndexNewStockAdjustmentController
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof InventoryIndexNewStockAdjustmentController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof InventoryIndexNewStockAdjustmentController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof InventoryIndexNewStockAdjustmentController
     */
    @tracked overlay;

    /**
     * The stock adjustment being created.
     *
     * @var {StockAdjustmentModel}
     */
    @tracked stockAdjustment = this.store.createRecord('stock-adjustment', { type: 'add', approval_required: false });

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof InventoryIndexNewStockAdjustmentController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof InventoryIndexNewStockAdjustmentController
     */
    /**
     * Cancel left the record createRecord() had already put in the store behind on
     * every abandoned create — a fresh orphan each time the panel is reopened, for the
     * life of the session. Same defect the create panels had; this route reaches the
     * exit through transitionBack rather than an onPressCancel action, which is why it
     * was missed the first time round.
     */
    @action transitionBack() {
        const record = this.stockAdjustment;

        if (record?.isNew) {
            record.rollbackAttributes();
        }

        return this.hostRouter.transitionTo('console.pallet.inventory.adjustments');
    }

    /**
     * Trigger a route refresh and focus the new product created.
     *
     * @param {StockAdjustmentModel} stockAdjustment
     * @return {Promise}
     * @memberof InventoryIndexNewStockAdjustmentController
     */
    @action onAfterSave() {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.hostRouter.transitionTo('console.pallet.inventory.adjustments');
    }

    /**
     * Resets the form with a new Inventory record
     *
     * @memberof InventoryIndexNewStockAdjustmentController
     */
    resetForm() {
        this.stockAdjustment = this.store.createRecord('stock-adjustment', { type: 'add', approval_required: false });
    }
}
