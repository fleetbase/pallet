import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsCycleCountsDetailsController extends Controller {
    @service hostRouter;
    @service intl;

    @tracked view = 'details';
    @tracked queryParams = ['view'];

    /**
     * Counting happens on the `.count` sheet, not here. Approval is still absent: it
     * posts stock adjustments, and §F wants variance above a threshold routed to review
     * rather than posted from a button on a read-only document.
     */
    get actionButtons() {
        if (this.model?.status !== 'in_progress') {
            return [];
        }

        return [
            {
                icon: 'clipboard-list',
                type: 'primary',
                text: this.intl.t('operations.cycle-counts.count.title'),
                fn: () => this.hostRouter.transitionTo('console.pallet.operations.cycle-counts.count', this.model.public_id),
            },
        ];
    }

    @action transitionBack() {
        return this.hostRouter.transitionTo('console.pallet.operations.cycle-counts.index');
    }
}
