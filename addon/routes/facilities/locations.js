import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class FacilitiesLocationsRoute extends Route {
    @service store;

    model() {
        return this.store.query('bin-location', { limit: 50, sort: '-created_at', with: ['warehouse', 'zone'] });
    }
}
