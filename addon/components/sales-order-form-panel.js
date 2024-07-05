import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import contextComponentCallback from '@fleetbase/ember-core/utils/context-component-callback';
import applyContextComponentArguments from '@fleetbase/ember-core/utils/apply-context-component-arguments';

export default class SalesOrderFormPanelComponent extends Component {
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
     * All possible salesOrder status options.
     *
     * @var {String}
     */
    @tracked statusOptions = ['pending', 'active', 'prospective', 'archived'];

    /**
     * Constructs the component and applies initial state.
     */
    constructor() {
        super(...arguments);
        this.salesOrder = this.args.salesOrder;
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
     * Saves the salesOrder changes.
     *
     * @action
     * @returns {Promise<any>}
     */
    @action save() {
        const { salesOrder } = this;

        this.loader.showLoader('.next-content-overlay-panel-container', { loadingMessage: 'Saving sales order...', preserveTargetPosition: true });
        this.isLoading = true;

        contextComponentCallback(this, 'onBeforeSave', salesOrder);

        try {
            return salesOrder
                .save()
                .then((salesOrder) => {
                    this.notifications.success(`Sales order (${salesOrder.id}) saved successfully.`);
                    contextComponentCallback(this, 'onAfterSave', salesOrder);
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
     * @memberof SalesOrderFormPanelComponent
     */

    /**
     * View the details of the salesOrder.
     *
     * @action
     */
    @action onViewDetails() {
        const isActionOverrided = contextComponentCallback(this, 'onViewDetails', this.salesOrder);

        if (!isActionOverrided) {
            this.contextPanel.focus(this.salesOrder, 'viewing');
        }
    }

    /**
     * Handles cancel button press.
     *
     * @action
     * @returns {any}
     */
    @action onPressCancel() {
        return contextComponentCallback(this, 'onPressCancel', this.salesOrder);
    }

    /**
     * Uploads a file to the server for the salesOrder.
     *
     * @param {File} file
     */
    @action setExpectedDeliveryDate(event) {
        const {
            target: { value },
        } = event;

        this.salesOrder.set('expected_delivery_at', new Date(value));
    }
}
