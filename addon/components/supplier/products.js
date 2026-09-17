import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

/**
 * Products this supplier supplies.
 *
 * `supplier` was in the product model's $filterParams with no filter method behind it,
 * so the parameter was accepted and ignored. This panel would have rendered the whole
 * catalogue as if the supplier stocked all of it — the filter method landed in the same
 * commit for that reason.
 */
export default class SupplierProductsComponent extends Component {
    @service store;
    @service notifications;

    @tracked products = [];

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
            this.products = yield this.store.query('pallet-product', { supplier, sort: 'name', limit: 25 });
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
