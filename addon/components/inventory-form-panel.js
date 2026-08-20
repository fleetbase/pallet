import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';
import contextComponentCallback from '@fleetbase/ember-core/utils/context-component-callback';
import applyContextComponentArguments from '@fleetbase/ember-core/utils/apply-context-component-arguments';

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

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    /**
     * Saves the fuel report changes.
     *
     * @action
     * @returns {Promise<any>}
     */
    @task *saveTask() {
        const { inventory } = this;

        this.loader.showLoader('.next-content-overlay-panel-container', { loadingMessage: 'Saving inventory...', preserveTargetPosition: true });
        contextComponentCallback(this, 'onBeforeSave', inventory);

        try {
            const savedInventory = yield inventory.save();
            this.notifications.success(`Inventory saved successfully.`);
            contextComponentCallback(this, 'onAfterSave', savedInventory);
            return savedInventory;
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.loader.removeLoader('.next-content-overlay-panel-container ');
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

    @action setVariant(variant) {
        this.inventory.variant = variant;
        this.inventory.variant_uuid = this.getRecordUuid(variant);
    }

    @action defaultProductSupplier(selectedProduct) {
        this.inventory.product = selectedProduct;
        this.inventory.product_uuid = this.getRecordUuid(selectedProduct);
        this.inventory.variant = null;
        this.inventory.variant_uuid = null;

        // most products have no supplier; findRecord(null) throws
        const supplierUuid = selectedProduct?.supplier_uuid;

        if (!supplierUuid) {
            this.inventory.setProperties({ supplier: null, supplier_uuid: null });

            return;
        }

        this.store
            .findRecord('supplier', supplierUuid)
            .then((supplier) => {
                this.inventory.setProperties({
                    product: selectedProduct,
                    supplier: supplier,
                    supplier_uuid: this.getRecordUuid(supplier),
                });
            })
            .catch((error) => {
                this.notifications.serverError(error);
            });
    }

    @action setSupplier(supplier) {
        this.inventory.supplier = supplier;
        this.inventory.supplier_uuid = this.getRecordUuid(supplier);
    }

    @action setWarehouse(warehouse) {
        this.inventory.warehouse = warehouse;
        this.inventory.warehouse_uuid = this.getRecordUuid(warehouse);
        this.inventory.binLocation = null;
        this.inventory.bin_location_uuid = null;
        this.inventory.zone = null;
        this.inventory.zone_uuid = null;
    }

    @action setBinLocation(binLocation) {
        this.inventory.binLocation = binLocation;
        this.inventory.bin_location_uuid = this.getRecordUuid(binLocation);
        this.inventory.zone_uuid = binLocation?.zone_uuid ?? this.inventory.zone_uuid;
    }

    @action setZone(zone) {
        this.inventory.zone = zone;
        this.inventory.zone_uuid = this.getRecordUuid(zone);
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
