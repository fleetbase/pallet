import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class WarehousesIndexDetailsController extends Controller {
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
     * Transitions back to the "warehouses.index" route.
     *
     * @method
     * @action
     * @returns {Transition} The transition object representing the route change.
     */
    @action transitionBack() {
        return this.transitionToRoute('warehouses.index');
    }

    /**
     * Transitions to the edit view for a specific warehouse.
     *
     * @method
     * @param {warehouseModel} warehouse - The warehouse to be edited.
     * @action
     * @returns {Transition} The transition object representing the route change.
     */
    @action onEdit(warehouse) {
        return this.transitionToRoute('warehouses.index.edit', warehouse);
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
