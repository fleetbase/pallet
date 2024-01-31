import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class ProductsIndexNewController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof ProductsIndexNewController
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof ProductsIndexNewController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof ProductsIndexNewController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof ProductsIndexNewController
     */
    @tracked overlay;

    /**
     * The product being created.
     *
     * @var {EntityModel}
     */
    @tracked product = this.store.createRecord('pallet-product', { type: 'pallet-product' });

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof ProductsIndexNewController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof ProductsIndexNewController
     */
    @action transitionBack() {
        return this.transitionToRoute('products.index');
    }

    /**
     * Trigger a route refresh and focus the new product created.
     *
     * @param {productModel} product
     * @return {Promise}
     * @memberof ProductsIndexNewController
     */
    @action onAfterSave(product) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('products.index.details', product).then(() => {
            this.resetForm();
        });
    }

    /**
     * Resets the form with a new product record
     *
     * @memberof ProductsIndexNewController
     */
    resetForm() {
        this.product = this.store.createRecord('pallet-product', { type: 'pallet-product', status: 'active' });
    }
}
