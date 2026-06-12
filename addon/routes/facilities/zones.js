import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class FacilitiesZonesRoute extends Route {
    @service store;

    model() {
        return this.store.query('warehouse-zone', { limit: 50, sort: '-created_at', with: ['warehouse'] });
    }
}
