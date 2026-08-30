import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class PurchaseOrdersIndexEditController extends Controller {
    /**
     * Inject the `hostRouter` service
     *
     * @memberof PurchaseOrdersIndexEditController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof PurchaseOrdersIndexEditController
     */
    @service modalsManager;
    @service events;
    @service notifications;
    @service intl;

    /**
     * The overlay component context.
     *
     * @memberof PurchaseOrdersIndexEditController
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
     * @memberof PurchaseOrdersIndexEditController
     */

    /**
     * The template bound @saveTask={{this.save}} and @onPressCancel={{this.cancel}}
     * but neither existed here, so the panel rendered no save button at all: you
     * could open the edit form, change anything you liked, and have no way to keep
     * it.
     */
    @task *save(purchaseOrder) {
        try {
            yield purchaseOrder.save();
            this.events.trackResourceUpdated(purchaseOrder);
            this.overlay?.close();

            yield this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index.details', purchaseOrder);
            this.notifications.success(
                this.intl.t('common.resource-updated-success', {
                    resource: 'Purchase Order',
                    resourceName: purchaseOrder.order_number ?? purchaseOrder.public_id,
                })
            );
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action cancel() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.orders.purchase-orders.index');
        }

        return this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index');
    }

    @action transitionBack(purchaseOrder) {
        if (purchaseOrder.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(purchaseOrder, {
                confirm: () => {
                    purchaseOrder.rollbackAttributes();
                    return this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index');
                },
            });
        }

        return this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index');
    }

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof PurchaseOrdersIndexEditController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When fuel-report details button is clicked in overlay.
     *
     * @param {PurchaseOrderModel} purchaseOrder
     * @return {Promise}
     * @memberof PurchaseOrdersIndexEditController
     */
    @action onViewDetails(purchaseOrder) {
        // check if fuel-report record has been edited and prompt for confirmation
        if (purchaseOrder.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(purchaseOrder);
        }

        return this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index.details', purchaseOrder);
    }

    /**
     * Trigger a route refresh and focus the new fuel-report created.
     *
     * @param {PurchaseOrderModel} purchaseOrder
     * @return {Promise}
     * @memberof PurchaseOrdersIndexEditController
     */
    @action onAfterSave(purchaseOrder) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index.details', purchaseOrder);
    }

    /**
     * Prompts the user to confirm if they wish to continue with unsaved changes.
     *
     * @method
     * @param {FuelReportModel} purchaseOrdert - The fuel-report object with unsaved changes.
     * @param {Object} [options={}] - Additional options for configuring the modal.
     * @returns {Promise} A promise that resolves when the user confirms, and transitions to a new route.
     * @memberof PurchaseOrdersIndexEditController
     */
    confirmContinueWithUnsavedChanges(purchaseOrder, options = {}) {
        // cancel() passes a route name rather than a modal options hash. That route
        // is the list, which has no dynamic segment — passing the record to it threw
        // "More context objects were passed than there are dynamic segments", so the
        // modal closed, rollbackAttributes() had already discarded the edits, and the
        // transition never ran. Cancel lost your work and left you on the form.
        if (typeof options === 'string') {
            const routeName = options;
            options = {
                confirm: async () => {
                    purchaseOrder.rollbackAttributes();
                    /*
                     * The modal tears itself down as the transition starts, and that
                     * teardown aborts the transition Ember has already begun. The
                     * navigation still completes — measured landing on the list — but
                     * awaiting it here turned the abort into an unhandled rejection and
                     * an uncaught TransitionAborted in the console on every cancel.
                     * Swallow only that; a real routing failure still throws.
                     */
                    try {
                        await this.hostRouter.transitionTo(routeName);
                    } catch (error) {
                        if (error?.name !== 'TransitionAborted') {
                            throw error;
                        }
                    }
                },
            };
        }

        return this.modalsManager.confirm({
            title: 'Continue Without Saving?',
            body: 'Unsaved changes to this purchase-order will be lost. Click continue to proceed.',
            acceptButtonText: 'Continue without saving',
            confirm: () => {
                purchaseOrder.rollbackAttributes();
                return this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index.details', purchaseOrder);
            },
            ...options,
        });
    }
}
