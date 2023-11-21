import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class InventoryIndexStockAdjustmentController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof InventoryIndexStockAdjustmentController
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof InventoryIndexStockAdjustmentController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof InventoryIndexStockAdjustmentController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof InventoryIndexStockAdjustmentController
     */
    @tracked overlay;

    /**
     * The product being created.
     *
     * @var {InventoryModel}
     */
    @tracked stockAdjustment = this.store.createRecord('stock-adjustment');

    @tracked inventory;

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof InventoryIndexStockAdjustmentController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof InventoryIndexStockAdjustmentController
     */
    @action transitionBack() {
        return this.transitionToRoute('inventory.index.stock-adjustment');
    }

    /**
     * Trigger a route refresh and focus the new product created.
     *
     * @param {InventoryModel} stockAdjustment
     * @return {Promise}
     * @memberof InventoryIndexStockAdjustmentController
     */
    @action onAfterSave(inventory) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('inventory.index.details', inventory).then(() => {
            this.resetForm();
        });
    }

    /**
     * Resets the form with a new Inventory record
     *
     * @memberof InventoryIndexStockAdjustmentController
     */
    resetForm() {
        this.stockAdjustment = this.store.createRecord('stock-adjustment');
    }
}
