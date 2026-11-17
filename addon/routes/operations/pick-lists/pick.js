import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsPickListsPickRoute extends Route {
    @service store;

    model({ public_id }) {
        return this.store.findRecord('pick-list', public_id, {
            include: ['warehouse', 'wave', 'assignedTo', 'items.product', 'items.binLocation'].join(','),
        });
    }
}
