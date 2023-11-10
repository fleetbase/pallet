import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class ProductsIndexEditController extends Controller {
    /**
     * Inject the `hostRouter` service
     *
     * @memberof ProductsIndexEditController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof ProductsIndexEditController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof ProductsIndexEditController
     */
    @tracked overlay;

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof productsIndexEditController
     */
    @action transitionBack(product) {
        // check if product record has been edited and prompt for confirmation
        if (product.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(product, {
                confirm: () => {
                    product.rollbackAttributes();
                    return this.transitionToRoute('management.products.index');
                },
            });
        }

        return this.transitionToRoute('products.index');
    }

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof ProductsIndexEditController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When product details button is clicked in overlay.
     *
     * @param {ProductModel} product
     * @return {Promise}
     * @memberof ProductsIndexEditController
     */
    @action onViewDetails(product) {
        // check if product record has been edited and prompt for confirmation
        if (product.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(product);
        }

        return this.transitionToRoute('products.index.details', product);
    }

    /**
     * Trigger a route refresh and focus the new product created.
     *
     * @param {ProductModel} product
     * @return {Promise}
     * @memberof ProductsIndexEditController
     */
    @action onAfterSave(product) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('products.index.details', product);
    }

    /**
     * Prompts the user to confirm if they wish to continue with unsaved changes.
     *
     * @method
     * @param {ProductModel} product - The product object with unsaved changes.
     * @param {Object} [options={}] - Additional options for configuring the modal.
     * @returns {Promise} A promise that resolves when the user confirms, and transitions to a new route.
     * @memberof ProductsIndexEditController
     */
    confirmContinueWithUnsavedChanges(product, options = {}) {
        return this.modalsManager.confirm({
            title: 'Continue Without Saving?',
            body: 'Unsaved changes to this product will be lost. Click continue to proceed.',
            acceptButtonText: 'Continue without saving',
            confirm: () => {
                product.rollbackAttributes();
                return this.transitionToRoute('products.index.details', product);
            },
            ...options,
        });
    }
}
