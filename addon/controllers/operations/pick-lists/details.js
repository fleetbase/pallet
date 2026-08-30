import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsPickListsDetailsController extends Controller {
    @service hostRouter;
    @service intl;

    @tracked view = 'details';
    @tracked queryParams = ['view'];

    /**
     * SCREENS.md §F puts the picking itself on a separate `.pick` screen — scan-first
     * and keyboard-driven — which does not exist yet. Until it does this document is
     * read-only rather than offering a half-measure that picks from a table, which §F
     * names as the must-never for this resource.
     */
    get actionButtons() {
        return [];
    }

    @action transitionBack() {
        return this.hostRouter.transitionTo('console.pallet.operations.pick-lists.index');
    }
}
