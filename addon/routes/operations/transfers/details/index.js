import Route from '@ember/routing/route';

export default class OperationsTransfersDetailsIndexRoute extends Route {
    /**
     * The record is loaded by the parent details route; without this the overview
     * renders with no model.
     */
    model() {
        return this.modelFor('operations.transfers.details');
    }
}
