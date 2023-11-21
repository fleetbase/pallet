import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class BatchIndexEditController extends Controller {
    /**
     * Inject the `hostRouter` service
     *
     * @memberof ManagementBatchsIndexEditController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof ManagementBatchsIndexEditController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof ManagementBatchsIndexEditController
     */
    @tracked overlay;

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof ManagementBatchsIndexEditController
     */
    @action transitionBack(batch) {
        if (batch.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(batch, {
                confirm: () => {
                    batch.rollbackAttributes();
                    return this.transitionToRoute('batch.index');
                },
            });
        }

        return this.transitionToRoute('batch.index');
    }

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof ManagementBatchsIndexEditController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When batch details button is clicked in overlay.
     *
     * @param {BatchModel} batch
     * @return {Promise}
     * @memberof ManagementBatchsIndexEditController
     */
    @action onViewDetails(batch) {
        // check if batch record has been edited and prompt for confirmation
        if (batch.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(batch);
        }

        return this.transitionToRoute('batch.index.details', batch);
    }

    /**
     * Trigger a route refresh and focus the new batch created.
     *
     * @param {BatchModel} batch
     * @return {Promise}
     * @memberof ManagementBatchsIndexEditController
     */
    @action onAfterSave(batch) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('batch.index.details', batch);
    }

    /**
     * Prompts the user to confirm if they wish to continue with unsaved changes.
     *
     * @method
     * @param {BatchModel} batch - The batch object with unsaved changes.
     * @param {Object} [options={}] - Additional options for configuring the modal.
     * @returns {Promise} A promise that resolves when the user confirms, and transitions to a new route.
     * @memberof ManagementBatchsIndexEditController
     */
    confirmContinueWithUnsavedChanges(batch, options = {}) {
        return this.modalsManager.confirm({
            title: 'Continue Without Saving?',
            body: 'Unsaved changes to this batch will be lost. Click continue to proceed.',
            acceptButtonText: 'Continue without saving',
            confirm: () => {
                batch.rollbackAttributes();
                return this.transitionToRoute('batch.index.details', batch);
            },
            ...options,
        });
    }
}
