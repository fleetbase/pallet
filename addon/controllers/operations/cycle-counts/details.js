import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsCycleCountsDetailsController extends Controller {
    @service hostRouter;

    @tracked view = 'details';
    @tracked queryParams = ['view'];

    /**
     * Counting happens on the `.count` sheet §F specifies — scan-first, blind, with the
     * audited supervisor reveal. That screen does not exist yet, and approval posts
     * stock adjustments, so neither belongs on a read-only document as a shortcut.
     */
    get actionButtons() {
        return [];
    }

    @action transitionBack() {
        return this.hostRouter.transitionTo('console.pallet.operations.cycle-counts.index');
    }
}
