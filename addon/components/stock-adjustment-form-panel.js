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
     * Fuel Report status
     * @type {Array}
     */
    @tracked statusOptions = ['draft', 'pending-approval', 'approved', 'rejected', 'revised', 'submitted', 'in-review', 'confirmed', 'processed', 'archived', 'cancelled'];

    /**
     * Constructs the component and applies initial state.
     */
    constructor() {
        super(...arguments);
        this.stockAdjustment = this.args.stockAdjustment;
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
     * Saves the fuel report changes.
     *
     * @action
     * @returns {Promise<any>}
     */
    @task *saveTask() {
        const { stockAdjustment } = this;

        this.loader.showLoader('.next-content-overlay-panel-container', { loadingMessage: 'Saving stockAdjustment...', preserveTargetPosition: true });
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
     * View the details of the fuel-report.
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
