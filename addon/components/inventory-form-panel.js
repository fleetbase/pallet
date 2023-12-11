import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import contextComponentCallback from '../utils/context-component-callback';
import applyContextComponentArguments from '../utils/apply-context-component-arguments';

export default class InventoryFormPanelComponent extends Component {
    /**
     * @service store
     */
    @service store;

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
     * Fuel Report status
     * @type {Array}
     */
    @tracked statusOptions = ['draft', 'pending-approval', 'approved', 'rejected', 'revised', 'submitted', 'in-review', 'confirmed', 'processed', 'archived', 'cancelled'];

    /**
     * Constructs the component and applies initial state.
     */
    constructor() {
        super(...arguments);
        this.inventory = this.args.inventory;

        // set batch if provived via component
        if (!this.inventory.batch) {
            this.inventory.batch = this.args.inventory.batch || this.store.createRecord('batch');
        }

        applyContextComponentArguments(this);
        this.setDefaultBatchValues();
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
     * Saves the fuel report changes.
     *
     * @action
     * @returns {Promise<any>}
     */
    @action save() {
        const { inventory } = this;

        this.loader.showLoader('.next-content-overlay-panel-container', { loadingMessage: 'Saving inventory...', preserveTargetPosition: true });
        this.isLoading = true;

        contextComponentCallback(this, 'onBeforeSave', inventory);

        try {
            return inventory
                .save()
                .then((inventory) => {
                    this.notifications.success(`Inventory saved successfully.`);
                    contextComponentCallback(this, 'onAfterSave', inventory);
                })
                .catch((error) => {
                    console.error(error);
                    this.notifications.serverError(error);
                })
                .finally(() => {
                    this.loader.removeLoader('.next-content-overlay-panel-container ');
                    this.isLoading = false;
                });
        } catch (error) {
            console.error(error);
            this.loader.removeLoader('.next-content-overlay-panel-container ');
            this.isLoading = false;
        }
    }

    /**
     * View the details of the fuel-report.
     *
     * @action
     */
    @action onViewDetails() {
        const isActionOverrided = contextComponentCallback(this, 'onViewDetails', this.inventory);

        if (!isActionOverrided) {
            this.contextPanel.focus(this.inventory, 'viewing');
        }
    }

    /**
     * Handles cancel button press.
     *
     * @action
     * @returns {any}
     */
    @action onPressCancel() {
        return contextComponentCallback(this, 'onPressCancel', this.inventory);
    }

    @action defaultProductSupplier(selectedProduct) {
        this.store
            .findRecord('supplier', selectedProduct.supplier_uuid)
            .then((supplier) => {
                this.inventory.setProperties({
                    product: selectedProduct,
                    supplier: supplier,
                });
            })
            .catch((error) => {
                console.error('Error fetching supplier:', error);
            });
    }

    @action setDefaultBatchValues() {
        const currentDate = new Date().toISOString().split('T')[0];

        if (!this.inventory.batch) {
            this.inventory.batch = this.store.createRecord('batch');
        }

        this.inventory.batch.set('batch_number', currentDate);
    }

    @action setExpiryDate(event) {
        const {
            target: { value },
        } = event;

        this.inventory.set('expiry_date_at', new Date(value));
    }
}
