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
     * Picking happens on the `.pick` screen, never from this table — §F's must-never for
     * this resource is presenting the route as an editable grid with no current-line
     * focus. This document stays a read-only view of the route; the button is the way in.
     */
    get actionButtons() {
        if (this.model?.status !== 'in_progress') {
            return [];
        }

        return [
            {
                icon: 'clipboard-list',
                type: 'primary',
                text: this.intl.t('operations.pick-lists.pick.title'),
                fn: () => this.hostRouter.transitionTo('console.pallet.operations.pick-lists.pick', this.model.public_id),
            },
        ];
    }

    @action transitionBack() {
        return this.hostRouter.transitionTo('console.pallet.operations.pick-lists.index');
    }
}
