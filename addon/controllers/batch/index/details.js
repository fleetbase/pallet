import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class BatchIndexDetailsController extends Controller {
    @service hostRouter;

    /**
     * The currently active view tab ('details' by default).
     *
     * @type {String}
     * @tracked
     */
    @tracked view = 'details';

    /**
     * An array of query parameters to be serialized in the URL.
     *
     * @type {String[]}
     * @tracked
     */
    @tracked queryParams = ['view'];

    /**
     * Transitions back to the "batch.index" route.
     *
     * @method
     * @action
     * @returns {Transition} The transition object representing the route change.
     */
    @action transitionBack() {
        return this.transitionToRoute('batch.index');
    }

    /**
     * Transitions to the edit view for a specific vehicle.
     *
     * @param {BatchModel} batch
     * @method
     * @action
     * @returns {Transition} The transition object representing the route change.
     */
    @action onEdit(batch) {
        return this.transitionToRoute('batch.index.edit', batch);
    }

    /**
     * Updates the active view tab.
     *
     * @method
     * @param {String} tab - The name of the tab to activate.
     * @action
     */
    @action onTabChanged(tab) {
        this.view = tab;
    }
}
