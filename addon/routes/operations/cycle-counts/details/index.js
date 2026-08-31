import Route from '@ember/routing/route';

export default class OperationsCycleCountsDetailsIndexRoute extends Route {
    model() {
        return this.modelFor('operations.cycle-counts.details');
    }
}
