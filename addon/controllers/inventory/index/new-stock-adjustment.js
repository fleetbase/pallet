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
    @tracked stockAdjustment = this.store.createRecord('stock-adjustment');

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
    @action transitionBack() {
        return this.transitionToRoute('inventory.index');
    }

    /**
     * Trigger a route refresh and focus the new product created.
     *
     * @param {StockAdjustmentModel} stockAdjustment
     * @return {Promise}
     * @memberof InventoryIndexNewStockAdjustmentController
     */
    @action onAfterSave(stockAdjustment) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('inventory.index');
    }

    /**
     * Resets the form with a new Inventory record
     *
     * @memberof InventoryIndexNewStockAdjustmentController
     */
    resetForm() {
        this.stockAdjustment = this.store.createRecord('stock-adjustment');
    }
}
