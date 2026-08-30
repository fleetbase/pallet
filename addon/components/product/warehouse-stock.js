import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

/**
 * Where this product's stock actually is.
 *
 * SCREENS.md §B asks the quantity panel for "the six-slot set, then a per-warehouse
 * breakdown table". The panel had the totals and nothing else, so "Available 94" was
 * 76 units in Singapore and 18 in Kuala Lumpur presented as one number with no way to
 * tell — the same ambiguity the product list carried, and the reason §B forbids an
 * unlabelled cross-warehouse sum.
 *
 * A breakdown answers it better than a label can: rather than telling the reader the
 * number is a sum, it shows them the parts.
 *
 * Loaded on demand — the panel is collapsed by default, so most visits never open it and
 * eagerly fetching would put a request on every product view to serve the minority who
 * want it. Generator task, not `task(async () => {})`, which Babel does not compile in
 * this engine.
 */
export default class ProductWarehouseStockComponent extends Component {
    @service store;
    @service notifications;

    @tracked rows = [];
    @tracked error = null;

    get productUuid() {
        return this.args.resource?.uuid ?? this.args.resource?.id;
    }

    @task({ drop: true })
    *load() {
        const product = this.productUuid;

        if (!product) {
            return;
        }

        try {
            const records = yield this.store.query('inventory', {
                product,
                with: ['warehouse'],
                limit: 200,
            });

            // Grouped here rather than asked of the API: a product sits in few enough
            // warehouses that a second endpoint would cost more than it saves, and the
            // rows are the same records the ledger and batch panels already load.
            const byWarehouse = new Map();

            records.forEach((record) => {
                const warehouse = record.warehouse;
                const key = warehouse?.id ?? record.warehouse_uuid ?? 'unassigned';

                if (!byWarehouse.has(key)) {
                    byWarehouse.set(key, {
                        key,
                        name: warehouse?.name ?? null,
                        onHand: 0,
                        reserved: 0,
                        available: 0,
                    });
                }

                const row = byWarehouse.get(key);

                row.onHand += Number(record.quantity) || 0;
                row.reserved += Number(record.reserved_quantity) || 0;
                row.available += Number(record.available_quantity) || 0;
            });

            this.rows = [...byWarehouse.values()].sort((a, b) => b.onHand - a.onHand || (a.name ?? '').localeCompare(b.name ?? ''));
            this.error = null;
        } catch (error) {
            this.error = error;
            this.notifications.serverError(error);
        }
    }
}
