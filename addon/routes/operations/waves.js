import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsWavesRoute extends Route {
    @service store;

    model() {
        return this.store.query('wave', { limit: 50, sort: '-created_at', with: ['warehouse'] });
    }
}
