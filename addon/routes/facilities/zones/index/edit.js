import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class ZonesIndexEditRoute extends Route {
    @service store;

    model({ public_id }) {
        return this.store.findRecord('warehouse-zone', public_id);
    }
}
