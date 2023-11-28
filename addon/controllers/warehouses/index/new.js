import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class WarehousesIndexNewController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof WarehousesIndexNewController
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof WarehousesIndexNewController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof WarehousesIndexNewController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof WarehousesIndexNewController
     */
    @tracked overlay;

    /**
     * The warehouse being created.
     *
     * @var {PlaceModel}
     */
    @tracked warehouse = this.store.createRecord('warehouse', { type: 'pallet-warehouse', status: 'active', meta: {}, sections: []});

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof WarehousesIndexNewController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof WarehousesIndexNewController
     */
    @action transitionBack() {
        return this.transitionToRoute('warehouses.index');
    }

    /**
     * Trigger a route refresh and focus the new warehouse created.
     *
     * @param {warehouseModel} warehouse
     * @return {Promise}
     * @memberof WarehousesIndexNewController
     */
    @action onAfterSave(warehouse) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('warehouses.index.details', warehouse).then(() => {
            this.resetForm();
        });
    }

    /**
     * Resets the form with a new warehouse record
     *
     * @memberof WarehousesIndexNewController
     */
    resetForm() {
        this.warehouse = this.store.createRecord('warehouse', { type: 'pallet-warehouse', status: 'active', meta: {}, sections: []});
    }
}
