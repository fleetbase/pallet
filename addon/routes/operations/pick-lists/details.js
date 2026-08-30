import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsPickListsDetailsRoute extends Route {
    @service store;

    queryParams = { view: { refreshModel: false } };

    model({ public_id }) {
        return this.store.findRecord('pick-list', public_id, {
            include: ['warehouse', 'wave', 'assignedTo', 'items.product', 'items.variant', 'items.binLocation'].join(','),
        });
    }
}
