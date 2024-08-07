import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import contextComponentCallback from '@fleetbase/ember-core/utils/context-component-callback';
import applyContextComponentArguments from '@fleetbase/ember-core/utils/apply-context-component-arguments';

export default class SupplierFormPanelComponent extends Component {
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
        this.supplier = this.args.supplier;
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
    @action save() {
        const { supplier } = this;

        this.loader.showLoader('.next-content-overlay-panel-container', { loadingMessage: 'Saving supplier...', preserveTargetPosition: true });
        this.isLoading = true;

        contextComponentCallback(this, 'onBeforeSave', supplier);

        try {
            return supplier
                .save()
                .then((supplier) => {
                    this.notifications.success(`Supplier saved successfully.`);
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
     * View the details of the fuel-report.
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
