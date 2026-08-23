import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class SalesOrdersIndexEditController extends Controller {
    /**
     * Inject the `hostRouter` service
     *
     * @memberof SalesOrdersIndexEditController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof SalesOrdersIndexEditController
     */
    @service modalsManager;
    @service events;
    @service notifications;
    @service intl;

    /**
     * The overlay component context.
     *
     * @memberof SalesOrdersIndexEditController
     */
    @tracked overlay;

    /**
     * The panel header's action buttons. The template binds this; without it the
     * edit panel renders no way back to the record's details.
     */
    get actionButtons() {
        return [
            {
                icon: 'eye',
                fn: () => this.onViewDetails(this.model),
            },
        ];
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof SalesOrdersIndexEditController
     */

    /**
     * The template bound @saveTask={{this.save}} and @onPressCancel={{this.cancel}}
     * but neither existed here, so the panel rendered no save button at all: you
     * could open the edit form, change anything you liked, and have no way to keep
     * it.
     */
    @task *save(salesOrder) {
        try {
            yield salesOrder.save();
            this.events.trackResourceUpdated(salesOrder);
            this.overlay?.close();

            yield this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index.details', salesOrder);
            this.notifications.success(
                this.intl.t('common.resource-updated-success', {
                    resource: 'Sales Order',
                    resourceName: salesOrder.order_number ?? salesOrder.public_id,
                })
            );
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action cancel() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.orders.sales-orders.index');
        }

        return this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index');
    }

    @action transitionBack(salesOrder) {
        if (salesOrder.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(salesOrder, {
                confirm: () => {
                    salesOrder.rollbackAttributes();
                    return this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index');
                },
            });
        }

        return this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index');
    }

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof SalesOrdersIndexEditController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When fuel-report details button is clicked in overlay.
     *
     * @param {SalesOrderModel} salesOrder
     * @return {Promise}
     * @memberof SalesOrdersIndexEditController
     */
    @action onViewDetails(salesOrder) {
        // check if fuel-report record has been edited and prompt for confirmation
        if (salesOrder.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(salesOrder);
        }

        return this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index.details', salesOrder);
    }

    /**
     * Trigger a route refresh and focus the new fuel-report created.
     *
     * @param {SalesOrderModel} salesOrder
     * @return {Promise}
     * @memberof SalesOrdersIndexEditController
     */
    @action onAfterSave(salesOrder) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index.details', salesOrder);
    }

    /**
     * Prompts the user to confirm if they wish to continue with unsaved changes.
     *
     * @method
     * @param {FuelReportModel} salesOrdert - The fuel-report object with unsaved changes.
     * @param {Object} [options={}] - Additional options for configuring the modal.
     * @returns {Promise} A promise that resolves when the user confirms, and transitions to a new route.
     * @memberof SalesOrdersIndexEditController
     */
    confirmContinueWithUnsavedChanges(salesOrder, options = {}) {
        // cancel() passes a route name rather than a modal options hash. That route
        // is the list, which has no dynamic segment — passing the record to it threw
        // "More context objects were passed than there are dynamic segments", so the
        // modal closed, rollbackAttributes() had already discarded the edits, and the
        // transition never ran. Cancel lost your work and left you on the form.
        if (typeof options === 'string') {
            const routeName = options;
            options = {
                confirm: async () => {
                    salesOrder.rollbackAttributes();
                    await this.hostRouter.transitionTo(routeName);
                },
            };
        }

        return this.modalsManager.confirm({
            title: 'Continue Without Saving?',
            body: 'Unsaved changes to this sales-order will be lost. Click continue to proceed.',
            acceptButtonText: 'Continue without saving',
            confirm: () => {
                salesOrder.rollbackAttributes();
                return this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index.details', salesOrder);
            },
            ...options,
        });
    }
}
