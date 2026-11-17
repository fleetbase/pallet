import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsWavesDetailsRoute extends Route {
    @service store;

    queryParams = { view: { refreshModel: false } };

    /**
     * The document can be reached directly by url, so it asks for its own relations
     * rather than relying on the list having run.
     */
    model({ public_id }) {
        return this.store.findRecord('wave', public_id, {
            include: ['warehouse', 'pickLists'].join(','),
        });
    }
}
