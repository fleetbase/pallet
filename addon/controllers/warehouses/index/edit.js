import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class WarehousesIndexEditController extends Controller {
    /**
     * Inject the `hostRouter` service
     *
     * @memberof WarehousesIndexEditController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof WarehousesIndexEditController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof WarehousesIndexEditController
     */
    @tracked overlay;

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof warehousesIndexEditController
     */
    @action transitionBack(warehouse) {
        // check if warehouse record has been edited and prompt for confirmation
        if (warehouse.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(warehouse, {
                confirm: () => {
                    warehouse.rollbackAttributes();
                    return this.transitionToRoute('management.warehouses.index');
                },
            });
        }

        return this.transitionToRoute('warehouses.index');
    }

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof WarehousesIndexEditController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When warehouse details button is clicked in overlay.
     *
     * @param {WarehouseModel} warehouse
     * @return {Promise}
     * @memberof WarehousesIndexEditController
     */
    @action onViewDetails(warehouse) {
        // check if warehouse record has been edited and prompt for confirmation
        if (warehouse.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(warehouse);
        }

        return this.transitionToRoute('warehouses.index.details', warehouse);
    }

    /**
     * Trigger a route refresh and focus the new warehouse created.
     *
     * @param {WarehouseModel} warehouse
     * @return {Promise}
     * @memberof WarehousesIndexEditController
     */
    @action onAfterSave(warehouse) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('warehouses.index.details', warehouse);
    }

    /**
     * Prompts the user to confirm if they wish to continue with unsaved changes.
     *
     * @method
     * @param {WarehouseModel} warehouse - The warehouse object with unsaved changes.
     * @param {Object} [options={}] - Additional options for configuring the modal.
     * @returns {Promise} A promise that resolves when the user confirms, and transitions to a new route.
     * @memberof WarehousesIndexEditController
     */
    confirmContinueWithUnsavedChanges(warehouse, options = {}) {
        return this.modalsManager.confirm({
            title: 'Continue Without Saving?',
            body: 'Unsaved changes to this warehouse will be lost. Click continue to proceed.',
            acceptButtonText: 'Continue without saving',
            confirm: () => {
                warehouse.rollbackAttributes();
                return this.transitionToRoute('warehouses.index.details', warehouse);
            },
            ...options,
        });
    }
}
