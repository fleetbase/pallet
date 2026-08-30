import Route from '@ember/routing/route';

export default class OperationsWavesDetailsIndexRoute extends Route {
    model() {
        return this.modelFor('operations.waves.details');
    }
}
