import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class OperationsTransfersDetailsRoute extends Route {
    @service store;

    queryParams = {
        view: { refreshModel: false },
    };

    /**
     * The list already asks for these relations; the document needs them too, and it
     * can be reached directly by url, so it cannot rely on the list having run.
     */
    model({ public_id }) {
        return this.store.findRecord('stock-transfer', public_id, {
            include: ['fromWarehouse', 'toWarehouse', 'items.product', 'items.variant'].join(','),
        });
    }
}
