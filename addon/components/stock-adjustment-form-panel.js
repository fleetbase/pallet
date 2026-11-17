import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action, get } from '@ember/object';
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
     * @service intl
     */
    @service intl;

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

    /**
     * The inventory row this adjustment will land on.
     *
     * Resolved with the same rules the server uses in
     * StockAdjustmentController::resolveInventory — product + warehouse, matching
     * variant (or explicitly no variant), status active, most recent first. If the
     * preview used different rules it would confidently show a number the save would
     * not produce.
     */
    @tracked currentInventory = null;

    @task({ restartable: true })
    *loadCurrentInventory() {
        const { product_uuid: product, warehouse_uuid: warehouse, variant_uuid: variant } = this.stockAdjustment;

        if (!product || !warehouse) {
            this.currentInventory = null;
            return;
        }

        try {
            const matches = yield this.store.query('inventory', {
                product,
                warehouse,
                status: 'active',
                sort: '-created_at',
                limit: 1,
            });

            const [match] = matches.slice();
            // Only claim a match when the variant agrees, the way the server's
            // whereNull/where split does.
            const matchVariant = match ? (match.variant_uuid ?? null) : null;
            this.currentInventory = match && matchVariant === (variant ?? null) ? match : null;
        } catch (error) {
            this.currentInventory = null;
            this.notifications.serverError(error);
        }
    }

    /**
     * On-hand before the adjustment. Null — rendered as a dash — when no inventory row
     * matches yet, which is different from a row holding zero.
     */
    get beforeQuantity() {
        return this.currentInventory ? Number(this.currentInventory.quantity ?? 0) : null;
    }

    /**
     * What on-hand becomes if this is saved.
     *
     * Mirrors StockAdjustmentController::calculateAfterQuantity exactly, including its
     * default branch. SCREENS.md section D's must-never for this screen is that the user
     * must not type the resulting quantity and have the delta inferred — the ledger needs
     * the delta and the reason. So the delta stays the input and this is the readout.
     */
    get afterQuantity() {
        const before = this.beforeQuantity;
        const requested = Number(this.stockAdjustment?.quantity ?? 0);

        if (before === null || !this.stockAdjustment?.type) {
            return null;
        }

        switch (this.stockAdjustment.type) {
            case 'remove':
            case 'damage':
            case 'expiry':
            case 'loss':
                return before - requested;
            case 'correction':
            case 'count':
                return requested;
            default:
                return before + requested;
        }
    }

    /**
     * An adjustment awaiting approval does not move stock when it is saved, so the
     * button should not claim it does.
     */
    get saveButtonText() {
        if (this.stockAdjustment?.approval_required) {
            return this.intl.t('inventory.adjustments.submit-for-approval');
        }

        return this.stockAdjustment?.id ? this.intl.t('inventory.adjustments.save') : this.intl.t('inventory.adjustments.make');
    }

    get quantityLabel() {
        return ['correction', 'count'].includes(this.stockAdjustment.type) ? 'Target Quantity' : 'Adjustment Quantity';
    }

    get hasProductVariants() {
        // `product` is a belongsTo proxy, and Ember asserts on direct property
        // access against one. saveTask reads this getter, so the throw aborted
        // the whole save: no request was sent and nothing surfaced to the user.
        const product = this.stockAdjustment.product;

        return Boolean(product && get(product, 'has_variants'));
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
        this.loadCurrentInventory.perform();
    }

    @action setVariant(variant) {
        this.stockAdjustment.variant = variant;
        this.stockAdjustment.variant_uuid = this.getRecordUuid(variant);
        this.loadCurrentInventory.perform();
    }

    @action setWarehouse(warehouse) {
        this.stockAdjustment.warehouse = warehouse;
        this.stockAdjustment.warehouse_uuid = this.getRecordUuid(warehouse);
        this.stockAdjustment.inventory = null;
        this.stockAdjustment.inventory_uuid = null;
        this.loadCurrentInventory.perform();
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
