import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';
import contextComponentCallback from '@fleetbase/ember-core/utils/context-component-callback';
import applyContextComponentArguments from '@fleetbase/ember-core/utils/apply-context-component-arguments';

export default class StockAdjustmentFormPanelComponent extends Component {
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
     * Stock adjustment type options.
     * @type {Array}
     */
    @tracked stockAdjustmentTypeOptions = [
        { label: 'Add Stock', value: 'add' },
        { label: 'Remove Stock', value: 'remove' },
        { label: 'Correct Count', value: 'correction' },
        { label: 'Damage', value: 'damage' },
        { label: 'Expiry', value: 'expiry' },
        { label: 'Loss', value: 'loss' },
        { label: 'Found Stock', value: 'found' },
    ];

    /**
     * Constructs the component and applies initial state.
     */
    constructor() {
        super(...arguments);
        this.stockAdjustment = this.args.stockAdjustment;
        // Defaults belong on the record at creation, not here. `??=` reads the
        // tracked property before writing it, so doing this during render
        // consumed the tag and then dirtied it — Ember's backtracking assertion
        // then aborted the whole panel and the screen rendered nothing.
        applyContextComponentArguments(this);
    }

    get selectedTypeOption() {
        return this.stockAdjustmentTypeOptions.find((option) => option.value === this.stockAdjustment.type);
    }

    get quantityLabel() {
        return ['correction', 'count'].includes(this.stockAdjustment.type) ? 'Target Quantity' : 'Adjustment Quantity';
    }

    get hasProductVariants() {
        return Boolean(this.stockAdjustment.product?.has_variants);
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    @action setType(option) {
        this.stockAdjustment.type = option?.value;
    }

    @action setProduct(product) {
        this.stockAdjustment.product = product;
        this.stockAdjustment.product_uuid = this.getRecordUuid(product);
        this.stockAdjustment.variant = null;
        this.stockAdjustment.variant_uuid = null;
    }

    @action setVariant(variant) {
        this.stockAdjustment.variant = variant;
        this.stockAdjustment.variant_uuid = this.getRecordUuid(variant);
    }

    @action setWarehouse(warehouse) {
        this.stockAdjustment.warehouse = warehouse;
        this.stockAdjustment.warehouse_uuid = this.getRecordUuid(warehouse);
        this.stockAdjustment.inventory = null;
        this.stockAdjustment.inventory_uuid = null;
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
     * Saves the stock adjustment changes.
     *
     * @action
     * @returns {Promise<any>}
     */
    @task *saveTask() {
        const { stockAdjustment } = this;

        if (!stockAdjustment.product_uuid) {
            return this.notifications.warning('Select a product for this stock adjustment.');
        }

        if (this.hasProductVariants && !stockAdjustment.variant_uuid) {
            return this.notifications.warning('Select a variant for this product.');
        }

        if (!stockAdjustment.warehouse_uuid) {
            return this.notifications.warning('Select a warehouse for this stock adjustment.');
        }

        if (!Number(stockAdjustment.quantity) || Number(stockAdjustment.quantity) <= 0) {
            return this.notifications.warning('Enter an adjustment quantity greater than zero.');
        }

        this.loader.showLoader('.next-content-overlay-panel-container', { loadingMessage: 'Saving stock adjustment...', preserveTargetPosition: true });
        contextComponentCallback(this, 'onBeforeSave', stockAdjustment);

        try {
            const savedStockAdjustment = yield stockAdjustment.save();
            this.notifications.success(`Stock Adjustment saved successfully.`);
            contextComponentCallback(this, 'onAfterSave', savedStockAdjustment);
            return savedStockAdjustment;
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.loader.removeLoader('.next-content-overlay-panel-container ');
        }
    }

    /**
     * View the details of the stock adjustment.
     *
     * @action
     */
    @action onViewDetails() {
        const isActionOverrided = contextComponentCallback(this, 'onViewDetails', this.stockAdjustment);

        if (!isActionOverrided) {
            this.contextPanel.focus(this.stockAdjustment, 'viewing');
        }
    }

    /**
     * Handles cancel button press.
     *
     * @action
     * @returns {any}
     */
    @action onPressCancel() {
        return contextComponentCallback(this, 'onPressCancel', this.stockAdjustment);
    }
}
