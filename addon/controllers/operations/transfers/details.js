import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsTransfersDetailsController extends Controller {
    @service hostRouter;
    @service intl;

    @tracked view = 'details';
    @tracked queryParams = ['view'];

    /**
     * Rendered into Layout::Section::Header's block — that component has no
     * @actionButtons argument, its only yield is the actions wormhole.
     *
     * The lifecycle actions (approve / ship / receive) deliberately stay on the list
     * for now: they live on the index controller, and moving them here would either
     * duplicate that logic or leave the two entry points able to drift. That is a
     * separate unit from giving the transfer a document to be read on.
     */
    get actionButtons() {
        return [];
    }

    @action transitionBack() {
        return this.hostRouter.transitionTo('console.pallet.operations.transfers.index');
    }
}
