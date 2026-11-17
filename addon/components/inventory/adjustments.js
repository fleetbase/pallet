import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

/**
 * Stock adjustments made against one inventory record.
 *
 * Sits beside the ledger: the ledger says what moved, this says who asked for it and
 * why. An adjustment carries a reason and an approval state that a bare movement row
 * cannot express.
 *
 * Generator syntax, not `task(async () => {})` — the async-arrow form is not compiled
 * by Babel in this engine and throws on construction.
 */
export default class InventoryAdjustmentsComponent extends Component {
    @service store;
    @service notifications;

    @tracked adjustments = [];

    get inventoryUuid() {
        return this.args.resource?.uuid ?? this.args.resource?.id;
    }

    @task({ drop: true })
    *load() {
        const inventory = this.inventoryUuid;

        if (!inventory) {
            return;
        }

        try {
            this.adjustments = yield this.store.query('stock-adjustment', {
                inventory,
                sort: '-created_at',
                limit: 50,
            });
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
