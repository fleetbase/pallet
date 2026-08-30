import Route from '@ember/routing/route';

export default class OperationsPickListsDetailsIndexRoute extends Route {
    model() {
        return this.modelFor('operations.pick-lists.details');
    }
}
