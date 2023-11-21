import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class BatchIndexNewController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof BatchIndexNewController
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof BatchIndexNewController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof BatchIndexNewController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof BatchIndexNewController
     */
    @tracked overlay;

    /**
     * The product being created.
     *
     * @var {BatchModel}
     */
    @tracked batch = this.store.createRecord('batch');

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof BatchIndexNewController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof BatchIndexNewController
     */
    @action transitionBack() {
        return this.transitionToRoute('batch.index');
    }

    /**
     * Trigger a route refresh and focus the new product created.
     *
     * @param {BatchModel} batch
     * @return {Promise}
     * @memberof BatchIndexNewController
     */
    @action onAfterSave(batch) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.transitionToRoute('batch.index.details', batch).then(() => {
            this.resetForm();
        });
    }

    /**
     * Resets the form with a new Batch record
     *
     * @memberof BatchIndexNewController
     */
    resetForm() {
        this.batch = this.store.createRecord('batch');
    }
}
