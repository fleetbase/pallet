import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class InventoryIndexEditController extends Controller {
    /**
     * Inject the `hostRouter` service
     *
     * @memberof ManagementInventorysIndexEditController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof ManagementInventorysIndexEditController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof ManagementInventorysIndexEditController
     */
    @tracked overlay;

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof ManagementInventorysIndexEditController
     */
    @action transitionBack(inventory) {
        if (inventory.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(inventory, {
                confirm: () => {
                    inventory.rollbackAttributes();
                    return this.transitionToRoute('inventory.index');
                },
            });
        }

        return this.transitionToRoute('inventory.index');
    }

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof ManagementInventorysIndexEditController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When inventory details button is clicked in overlay.
     *
     * @param {InventoryModel} inventory
     * @return {Promise}
     * @memberof ManagementInventorysIndexEditController
     */
    @action onViewDetails(inventory) {
        // check if inventory record has been edited and prompt for confirmation
        if (inventory.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(inventory);
        }

        return this.transitionToRoute('inventory.index.details', inventory);
    }

    /**
     * Trigger a route refresh and focus the new inventory created.
     *
     * @param {InventoryModel} inventory
     * @return {Promise}
     * @memberof ManagementInventorysIndexEditController
     */
    @action onAfterSave(inventory) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('inventory.index.details', inventory);
    }

    /**
     * Prompts the user to confirm if they wish to continue with unsaved changes.
     *
     * @method
     * @param {InventoryModel} inventory - The inventory object with unsaved changes.
     * @param {Object} [options={}] - Additional options for configuring the modal.
     * @returns {Promise} A promise that resolves when the user confirms, and transitions to a new route.
     * @memberof ManagementInventorysIndexEditController
     */
    confirmContinueWithUnsavedChanges(inventory, options = {}) {
        return this.modalsManager.confirm({
            title: 'Continue Without Saving?',
            body: 'Unsaved changes to this inventory will be lost. Click continue to proceed.',
            acceptButtonText: 'Continue without saving',
            confirm: () => {
                inventory.rollbackAttributes();
                return this.transitionToRoute('inventory.index.details', inventory);
            },
            ...options,
        });
    }
}
