import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class SuppliersIndexNewController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof ManagementSupplierIndexNewController
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof ManagementSupplierIndexNewController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof ManagementSupplierIndexNewController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof ManagementSupplierIndexNewController
     */
    @tracked overlay;

    /**
     * The supplier being created.
     *
     * @var {SupplierModel}
     */
    @tracked supplier = this.store.createRecord('supplier', { status: 'active' });

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof ManagementSuppliersIndexNewController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof ManagementSupplierIndexNewController
     */
    @action transitionBack() {
        return this.transitionToRoute('suppliers.index');
    }

    /**
     * Trigger a route refresh and focus the new supplier created.
     *
     * @param {SupplierModel} supplier
     * @return {Promise}
     * @memberof ManagementSuppliersIndexNewController
     */
    @action onAfterSave(supplier) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('suppliers.index.details', supplier).then(() => {
            this.resetForm();
        });
    }

    /**
     * Resets the form with a new supplier record
     *
     * @memberof ManagementSupplierIndexNewController
     */
    resetForm() {
        this.supplier = this.store.createRecord('supplier', { status: 'active' });
    }
}
