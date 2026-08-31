import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';
import contextComponentCallback from '@fleetbase/ember-core/utils/context-component-callback';
import applyContextComponentArguments from '@fleetbase/ember-core/utils/apply-context-component-arguments';

export default class BatchFormPanelComponent extends Component {
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
        this.batch = this.args.batch;
        applyContextComponentArguments(this);
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    @action setProduct(product) {
        this.batch.product = product;
        this.batch.product_uuid = this.getRecordUuid(product);
        this.batch.variant = null;
        this.batch.variant_uuid = null;
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
        const { batch } = this;

        this.loader.showLoader('.next-content-overlay-panel-container', { loadingMessage: 'Saving batch...', preserveTargetPosition: true });
        contextComponentCallback(this, 'onBeforeSave', batch);

        try {
            const savedBatch = yield batch.save();
            this.notifications.success(`Batch saved successfully.`);
            contextComponentCallback(this, 'onAfterSave', savedBatch);
            return savedBatch;
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
        const isActionOverrided = contextComponentCallback(this, 'onViewDetails', this.batch);

        if (!isActionOverrided) {
            this.contextPanel.focus(this.batch, 'viewing');
        }
    }

    /**
     * Handles cancel button press.
     *
     * @action
     * @returns {any}
     */
    @action onPressCancel() {
        return contextComponentCallback(this, 'onPressCancel', this.batch);
    }
}
