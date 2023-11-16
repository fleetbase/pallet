import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import contextComponentCallback from '../utils/context-component-callback';
import applyContextComponentArguments from '../utils/apply-context-component-arguments';
import getSupplierTypeOptions from '../utils/get-supplier-type-options';
import getSupplierStatusOptions from '../utils/get-supplier-status-options';

export default class SupplierFormPanelComponent extends Component {
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
     * The users supplier instance.
     * @type {SupplierModel|IntegratedSupplierModel}
     */
    @tracked supplier;

    /**
     * Specific types of suppliers which can be set as the type.
     *
     * @memberof SupplierFormPanelComponent
     */
    @tracked supplierTypeOptions = getSupplierTypeOptions();

    /**
     * Applicable status options for supplier.
     *
     * @memberof SupplierFormPanelComponent
     */
    @tracked supplierStatusOptions = getSupplierStatusOptions();

    /**
     * Constructs the component and applies initial state.
     */
    constructor() {
        super(...arguments);
        this.supplier = this.args.supplier;
        this.isEditing = typeof this.supplier.id === 'string';
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
     * Saves the supplier changes.
     *
     * @action
     * @returns {Promise<any>}
     */
    @action save() {
        const { supplier } = this;

        this.loader.showLoader('.next-content-overlay-panel-container', { loadingMessage: 'Saving supplier...', preserveTargetPosition: true });
        this.isLoading = true;

        contextComponentCallback(this, 'onBeforeSave', supplier);

        try {
            return supplier
                .save()
                .then((supplier) => {
                    this.notifications.success(`Supplier (${supplier.displayName}) saved successfully.`);
                    contextComponentCallback(this, 'onAfterSave', supplier);
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
     * Uploads a new logo for the supplier.
     *
     * @param {File} file
     * @memberof DriverFormPanelComponent
     */
    @action onUploadNewPhoto(file) {
        this.fetch.uploadFile.perform(
            file,
            {
                path: `uploads/${this.currentUser.companyId}/suppliers/${this.supplier.id}`,
                subject_uuid: this.supplier.id,
                subject_type: 'supplier',
                type: 'supplier_logo',
            },
            (uploadedFile) => {
                this.supplier.setProperties({
                    logo_uuid: uploadedFile.id,
                    logo_url: uploadedFile.url,
                    logo: uploadedFile,
                });
            }
        );
    }

    /**
     * Handle when supplier changed.
     *
     * @param {SupplierModel} supplier
     * @memberof SupplierFormPanelComponent
     */
    @action onSupplierChanged(supplier) {
        this.supplier = supplier;
    }

    /**
     * View the details of the supplier.
     *
     * @action
     */
    @action onViewDetails() {
        const isActionOverrided = contextComponentCallback(this, 'onViewDetails', this.supplier);

        if (!isActionOverrided) {
            this.contextPanel.focus(this.supplier, 'viewing');
        }
    }

    /**
     * Handles cancel button press.
     *
     * @action
     * @returns {any}
     */
    @action onPressCancel() {
        return contextComponentCallback(this, 'onPressCancel', this.supplier);
    }
}
