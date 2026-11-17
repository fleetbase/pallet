import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

/**
 * Purchase orders raised with this supplier.
 *
 * Loaded on demand — the panel is collapsed by default, so a visit that never opens it
 * should not pay for the request. Generator syntax, not `task(async () => {})`, which
 * this engine does not compile.
 */
export default class SupplierPurchaseOrdersComponent extends Component {
    @service store;
    @service notifications;

    @tracked orders = [];

    get supplierUuid() {
        return this.args.resource?.uuid ?? this.args.resource?.id;
    }

    @task({ drop: true })
    *load() {
        const supplier = this.supplierUuid;

        if (!supplier) {
            return;
        }

        try {
            this.orders = yield this.store.query('purchase-order', { supplier, sort: '-created_at', limit: 25 });
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
