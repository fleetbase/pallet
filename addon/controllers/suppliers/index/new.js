import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class SuppliersIndexNewController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof SuppliersIndexNewController
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof SuppliersIndexNewController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof SuppliersIndexNewController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof SuppliersIndexNewController
     */
    @tracked overlay;

    /**
     * The supplier being created.
     *
     * @var {EntityModel}
     */
    @tracked supplier = this.store.createRecord('pallet-supplier', { type: 'pallet-supplier' });

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof SuppliersIndexNewController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof SuppliersIndexNewController
     */
    @action transitionBack() {
        return this.transitionToRoute('suppliers.index');
    }

    /**
     * Trigger a route refresh and focus the new supplier created.
     *
     * @param {supplierModel} supplier
     * @return {Promise}
     * @memberof SuppliersIndexNewController
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
     * @memberof SuppliersIndexNewController
     */
    resetForm() {
        this.supplier = this.store.createRecord('pallet-supplier', { type: 'pallet-supplier', status: 'active' });
    }
}
