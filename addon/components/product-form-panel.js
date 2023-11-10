import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import contextComponentCallback from '../utils/context-component-callback';
import applyContextComponentArguments from '../utils/apply-context-component-arguments';

export default class ProductFormPanelComponent extends Component {
    /**
     * @service store
     */
    @service store;

    /**
     * @service fetch
     */
    @service fetch;

    /**
     * @service currentUser
     */
    @service currentUser;

    /**
     * @service notifications
     */
    @service notifications;

    /**
     * @service hostRouter
     */
    @service hostRouter;

    /**
     * @service loader
     */
    @service loader;

    /**
     * @service contextPanel
     */
    @service contextPanel;

    /**
     * Overlay context.
     * @type {any}
     */
    @tracked context;

    /**
     * Indicates whether the component is in a loading state.
     * @type {boolean}
     */
    @tracked isLoading = false;

    /**
     * All possible product types.
     *
     * @var {String}
     */
    @tracked productTypeOptions = ['product', 'customer'];

    /**
     * All possible product status options.
     *
     * @var {String}
     */
    @tracked productStatusOptions = ['pending', 'active', 'do-not-product', 'prospective', 'archived'];

    /**
     * Constructs the component and applies initial state.
     */
    constructor() {
        super(...arguments);
        this.product = this.args.product;
        applyContextComponentArguments(this);
    }

    /**
     * Sets the overlay context.
     *
     * @action
     * @param {OverlayContextObject} overlayContext
     */
    @action setOverlayContext(overlayContext) {
        this.context = overlayContext;
        contextComponentCallback(this, 'onLoad', ...arguments);
    }

    /**
     * Saves the product changes.
     *
     * @action
     * @returns {Promise<any>}
     */
    @action save() {
        const { product } = this;

        this.loader.showLoader('.next-content-overlay-panel-container', { loadingMessage: 'Saving product...', preserveTargetPosition: true });
        this.isLoading = true;

        contextComponentCallback(this, 'onBeforeSave', product);

        try {
            return product
                .save()
                .then((product) => {
                    this.notifications.success(`product (${product.name}) saved successfully.`);
                    contextComponentCallback(this, 'onAfterSave', product);
                })
                .catch((error) => {
                    this.notifications.serverError(error);
                })
                .finally(() => {
                    this.loader.removeLoader('.next-content-overlay-panel-container ');
                    this.isLoading = false;
                });
        } catch (error) {
            this.loader.removeLoader('.next-content-overlay-panel-container ');
            this.isLoading = false;
        }
    }

    /**
     * Uploads a new photo for the driver.
     *
     * @param {File} file
     * @memberof DriverFormPanelComponent
     */
    @action onUploadNewPhoto(file) {
        this.fetch.uploadFile.perform(
            file,
            {
                path: `uploads/${this.currentUser.companyId}/drivers/${this.product.id}`,
                subject_uuid: this.product.id,
                subject_type: `product`,
                type: `product_photo`,
            },
            (uploadedFile) => {
                this.product.setProperties({
                    photo_uuid: uploadedFile.id,
                    photo_url: uploadedFile.url,
                    photo: uploadedFile,
                });
            }
        );
    }

    /**
     * View the details of the product.
     *
     * @action
     */
    @action onViewDetails() {
        const isActionOverrided = contextComponentCallback(this, 'onViewDetails', this.product);

        if (!isActionOverrided) {
            this.contextPanel.focus(this.product, 'viewing');
        }
    }

    /**
     * Handles cancel button press.
     *
     * @action
     * @returns {any}
     */
    @action onPressCancel() {
        return contextComponentCallback(this, 'onPressCancel', this.product);
    }

    /**
     * Uploads a file to the server for the product.
     *
     * @param {File} file
     */
    uploadProductPhoto(file) {
        this.fetch.uploadFile.perform(
            file,
            {
                path: `uploads/${this.product.company_uuid}/products/${this.product.slug}`,
                subject_uuid: this.product.id,
                subject_type: 'product',
                type: 'product_photo',
            },
            (uploadedFile) => {
                this.product.setProperties({
                    photo_uuid: uploadedFile.id,
                    photo_url: uploadedFile.url,
                    photo: uploadedFile,
                });
            }
        );
    }
}
