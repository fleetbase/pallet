import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class InventoryIndexNewController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof InventoryIndexNewController
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof InventoryIndexNewController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof InventoryIndexNewController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof InventoryIndexNewController
     */
    @tracked overlay;

    /**
     * The product being created.
     *
     * @var {InventoryModel}
     */
    @tracked inventory = this.store.createRecord('inventory');

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof InventoryIndexNewController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof InventoryIndexNewController
     */
    @action transitionBack() {
        return this.transitionToRoute('inventory.index');
    }

    /**
     * Trigger a route refresh and focus the new product created.
     *
     * @param {InventoryModel} inventory
     * @return {Promise}
     * @memberof InventoryIndexNewController
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
     * @memberof InventoryIndexNewController
     */
    resetForm() {
        this.inventory = this.store.createRecord('inventory');
    }
}
